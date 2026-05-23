<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use App\Models\ErpSyncLog;
use App\Models\Category;
use App\Models\Setting;
use App\Services\ErpNextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ErpSyncController extends Controller
{
    public function __construct(private ErpNextService $erp) {}

    public function index()
    {
        $stats = [
            'pending' => Transaction::where('erp_sync_status', 'pending')->where('status', 'completed')->count(),
            'synced' => Transaction::where('erp_sync_status', 'synced')->count(),
            'failed' => Transaction::where('erp_sync_status', 'failed')->count(),
        ];

        $recentLogs = ErpSyncLog::latest()->limit(20)->get();
        $failedTransactions = Transaction::where('erp_sync_status', 'failed')
            ->with('user')->latest()->limit(10)->get();

        $settings = [
            'erpnext_url'             => Setting::get('erpnext_url', env('ERPNEXT_URL')),
            'erpnext_api_key'         => Setting::get('erpnext_api_key', env('ERPNEXT_API_KEY')),
            'erpnext_api_secret'      => Setting::get('erpnext_api_secret'),
            'erpnext_company'         => Setting::get('erpnext_company', env('ERPNEXT_COMPANY')),
            'erpnext_pos_profile'     => Setting::get('erpnext_pos_profile', env('ERPNEXT_POS_PROFILE')),
            'erpnext_walkin_customer' => Setting::get('erpnext_walkin_customer', 'Walk-in Customer'),
            'erpnext_price_list'      => Setting::get('erpnext_price_list', ''),
        ];

        return view('sync.index', compact('stats', 'recentLogs', 'failedTransactions', 'settings'));
    }

    public function testConnection()
    {
        $result = $this->erp->testConnection();
        return response()->json($result);
    }

    public function syncAll()
    {
        $result = $this->erp->syncPendingTransactions();
        return response()->json($result);
    }

    public function syncSingle(Transaction $transaction)
    {
        $transaction->load(['items.product', 'customer']);
        $result = $this->erp->syncTransaction($transaction);
        return response()->json($result);
    }

    public function retryFailed()
    {
        $failed = Transaction::where('erp_sync_status', 'failed')
            ->where('status', 'completed')
            ->update(['erp_sync_status' => 'pending']);

        return response()->json(['success' => true, 'reset' => $failed]);
    }

    public function pullProducts()
    {
        set_time_limit(0);

        $imported = 0;
        $updated  = 0;
        $page     = 0;
        $pageSize = 100;

        do {
            $result = $this->erp->pullProducts($pageSize, $page * $pageSize);

            if (!$result['success']) {
                return response()->json([
                    'success'  => false,
                    'error'    => $result['error'],
                    'imported' => $imported,
                    'updated'  => $updated,
                ], 422);
            }

            $batch = $result['data'];

            foreach ($batch as $item) {
                $category = null;
                if (!empty($item['item_group'])) {
                    $category = Category::firstOrCreate(
                        ['name' => $item['item_group']],
                        ['slug' => Str::slug($item['item_group']), 'erp_item_group' => $item['item_group']]
                    );
                }

                $exists   = Product::where('erp_item_code', $item['name'])->first();
                $erpImage = $item['image'] ?? null;

                $data = [
                    'name'          => $item['item_name'] ?? $item['name'],
                    'sku'           => $item['item_code'] ?? $item['name'],
                    'price'         => (float) ($item['standard_rate'] ?? 0),
                    'cost_price'    => (float) ($item['valuation_rate'] ?? 0),
                    'unit'          => $item['stock_uom'] ?? 'Nos',
                    'barcode'       => $item['barcode'] ?? null,
                    'category_id'   => $category?->id,
                    'erp_item_code' => $item['name'],
                    'erp_last_sync' => now(),
                    'is_active'     => !($item['disabled'] ?? false),
                ];

                // Download image only when ERPNext has one and the path has changed
                if ($erpImage && $erpImage !== ($exists?->erp_image)) {
                    $localPath = $this->erp->downloadProductImage($erpImage, $item['name']);
                    if ($localPath) {
                        $data['image']     = $localPath;
                        $data['erp_image'] = $erpImage;
                    }
                } elseif (!$erpImage && $exists?->erp_image) {
                    // Image removed on ERPNext side — clear local reference too
                    $data['image']     = null;
                    $data['erp_image'] = null;
                }

                if ($exists) {
                    $exists->update($data);
                    $updated++;
                } else {
                    Product::create(array_merge($data, ['track_stock' => true]));
                    $imported++;
                }
            }

            $page++;

        } while (count($batch) >= $pageSize);

        return response()->json([
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'total'    => $imported + $updated,
        ]);
    }

    public function pushCustomer(Customer $customer)
    {
        $result = $this->erp->pushCustomer($customer);
        return response()->json($result);
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'erpnext_url'             => 'required|url',
            'erpnext_api_key'         => 'required|string',
            'erpnext_api_secret'      => 'required|string',
            'erpnext_company'         => 'required|string',
            'erpnext_pos_profile'     => 'nullable|string',
            'erpnext_walkin_customer' => 'nullable|string|max:140',
            'erpnext_price_list'      => 'nullable|string|max:140',
        ]);

        foreach ($request->only(['erpnext_url','erpnext_api_key','erpnext_api_secret','erpnext_company','erpnext_pos_profile','erpnext_walkin_customer','erpnext_price_list']) as $k => $v) {
            Setting::set($k, $v, 'erpnext');
        }

        return response()->json(['success' => true]);
    }

    public function logs()
    {
        $logs = ErpSyncLog::latest()->paginate(30);
        return response()->json($logs);
    }
}
