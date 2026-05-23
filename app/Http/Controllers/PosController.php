<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        $products = Product::active()->with('category')->get();
        $customers = Customer::where('is_active', true)->get(['id', 'code', 'name', 'phone', 'loyalty_points']);
        $storeSettings      = SettingsController::storeSettings();
        $posClass           = $storeSettings['pos_class'] ?? '';
        $posProductDisplay  = $storeSettings['pos_product_display'] ?? 'image';
        $walkinCustomerName = \App\Models\Setting::get('erpnext_walkin_customer', 'Walk-in Customer');
        $erpBaseUrl         = rtrim(\App\Models\Setting::get('erpnext_url', ''), '/');
        return view('pos.index', compact('categories', 'products', 'customers', 'posClass', 'posProductDisplay', 'walkinCustomerName', 'erpBaseUrl'));
    }

    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');
        $categoryId = $request->get('category_id');

        $query = Product::active()->with('category');

        if ($term) {
            $query->search($term);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->limit(50)->get();

        return response()->json($products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'price' => $p->price,
                'stock' => $p->stock,
                'unit' => $p->unit,
                'tax_rate' => $p->tax_rate,
                'track_stock' => (bool) $p->track_stock,
                'image' => $p->image,
                'category' => $p->category?->name,
                'category_id' => $p->category_id,
                'category_color' => $p->category?->color ?? '#4285F4',
                'is_low_stock' => $p->isLowStock(),
            ];
        }));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $taxAmount = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = $item['quantity'];
                $price = $item['price'];
                $discount = $item['discount_amount'] ?? 0;
                $taxRate = $product->tax_rate ?? 0;
                $itemSubtotal = ($price * $qty) - $discount;
                $itemTax = $itemSubtotal * ($taxRate / 100);

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;

                // Update stock
                if ($product->track_stock) {
                    $product->decrement('stock', $qty);
                }

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $price,
                    'cost_price' => $product->cost_price,
                    'quantity' => $qty,
                    'discount_amount' => $discount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $itemTax,
                    'subtotal' => $itemSubtotal + $itemTax,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $discountAmount = $request->discount_amount ?? 0;
            $discountPercent = $request->discount_percent ?? 0;
            if ($discountPercent > 0) {
                $discountAmount = $subtotal * ($discountPercent / 100);
            }

            $total = $subtotal + $taxAmount - $discountAmount;
            $paidAmount = $request->paid_amount;
            $change = $paidAmount - $total;

            $transaction = Transaction::create([
                'invoice_no' => Transaction::generateInvoiceNo(),
                'user_id' => Auth::id(),
                'customer_id' => $request->customer_id,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_percent' => $discountPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => max(0, $change),
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
                'notes' => $request->notes,
                'pos_class' => $request->pos_class,
                'erp_sync_status' => 'pending',
            ]);

            $transaction->items()->insert(array_map(fn($i) => array_merge($i, ['transaction_id' => $transaction->id]), $itemsData));

            // Update customer total purchase
            if ($request->customer_id) {
                Customer::where('id', $request->customer_id)
                    ->increment('total_purchase', $total);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => $transaction->load('items.product', 'customer', 'user'),
                'invoice_no' => $transaction->invoice_no,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load('items.product', 'customer', 'user');
        $store = SettingsController::storeSettings();
        return view('pos.receipt', compact('transaction', 'store'));
    }

    public function printReceipt(Transaction $transaction)
    {
        $transaction->load('items.product', 'customer', 'user');
        $store = SettingsController::storeSettings();
        return view('pos.print-receipt', compact('transaction', 'store'));
    }
}
