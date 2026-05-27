<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use App\Models\ErpSyncLog;
use App\Models\Warehouse;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ErpNextService
{
    private Client $client;
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->baseUrl   = rtrim(\App\Models\Setting::get('erpnext_url', env('ERPNEXT_URL', '')), '/');
        $this->apiKey    = \App\Models\Setting::get('erpnext_api_key', env('ERPNEXT_API_KEY', ''));
        $this->apiSecret = \App\Models\Setting::get('erpnext_api_secret', env('ERPNEXT_API_SECRET', ''));

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'Authorization' => 'token ' . $this->apiKey . ':' . $this->apiSecret,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'verify' => false,
        ]);
    }

    // =========================================================
    // TEST CONNECTION
    // =========================================================
    public function testConnection(): array
    {
        try {
            if (empty($this->baseUrl)) {
                return ['success' => false, 'error' => 'URL ERP HPY kosong!'];
            }

            $response = $this->client->get('/api/method/frappe.auth.get_logged_user');
            $data     = json_decode($response->getBody()->getContents(), true);

            if (isset($data['message'])) {
                return ['success' => true, 'user' => $data['message']];
            }

            return ['success' => false, 'error' => 'Response tidak valid: ' . json_encode($data)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // SYNC TRANSACTIONS → ERPNext POS Invoice
    // =========================================================
    public function syncTransaction(Transaction $transaction): array
    {
        $payload = $this->buildPosInvoicePayload($transaction);

        try {
            $response = $this->client->post('/api/resource/POS Invoice', [
                'json' => $payload
            ]);

            $data    = json_decode($response->getBody()->getContents(), true);
            $docname = $data['data']['name'] ?? null;

            if ($docname) {
                $this->submitDoc('POS Invoice', $docname);
            }

            $transaction->update([
                'erp_pos_invoice' => $docname,
                'erp_synced_at'   => now(),
                'erp_sync_status' => 'synced',
                'erp_sync_error'  => null,
            ]);

            $this->logSync('transaction', $transaction->id, $transaction->invoice_no,
                'success', $payload, $data, $docname);

            return ['success' => true, 'docname' => $docname];

        } catch (RequestException $e) {
            $errorBody = $this->extractError($e);

            $transaction->update([
                'erp_sync_status' => 'failed',
                'erp_sync_error'  => $errorBody,
            ]);

            $this->logSync('transaction', $transaction->id, $transaction->invoice_no,
                'failed', $payload, null, null, $errorBody);

            Log::error("ERPNext sync failed for {$transaction->invoice_no}: {$errorBody}");
            return ['success' => false, 'error' => $errorBody];
        }
    }

    private function buildPosInvoicePayload(Transaction $transaction): array
    {
        $company    = \App\Models\Setting::get('erpnext_company', env('ERPNEXT_COMPANY'));
        $posProfile = \App\Models\Setting::get('erpnext_pos_profile', env('ERPNEXT_POS_PROFILE'));
        $priceList  = \App\Models\Setting::get('erpnext_price_list', '');

        $items = $transaction->items->map(function ($item) {
            // Fold diskon item ke dalam rate — kirim net rate langsung.
            // Ini menghindari konflik dengan ERPNext ketika price_list_rate = 0
            // (tidak ada price list), karena ERPNext akan menghitung diskon dari rate
            // yang kita kirim, bukan dari price list.
            $discAmt = (float) $item->discount_amount;
            $netRate = max(0, (float) $item->price - ($item->quantity > 0 ? $discAmt / $item->quantity : 0));
            $netAmount = $netRate * $item->quantity;
            return [
                'item_code' => $item->product->erp_item_code ?? $item->product_sku,
                'item_name' => $item->product_name,
                'qty'       => $item->quantity,
                'rate'      => $netRate,
                'amount'    => $netAmount,
                'uom'       => $item->product->unit ?? 'Nos',
            ];
        })->toArray();

        $payments = [];
        if ($transaction->payment_method === 'mixed' && $transaction->payment_details) {
            foreach ($transaction->payment_details as $method => $amount) {
                $payments[] = [
                    'mode_of_payment' => $this->mapPaymentMethod($method),
                    'amount'          => (float) $amount,
                ];
            }
        } else {
            $payments[] = [
                'mode_of_payment' => $this->mapPaymentMethod($transaction->payment_method),
                'amount'          => (float) $transaction->total,
            ];
        }

        $defaultWarehouse = Warehouse::getDefault()?->name;

        $payload = [
            'doctype'                        => 'POS Invoice',
            'naming_series'                  => 'ACC-PSINV-.YYYY.-',
            'pos_profile'                    => $posProfile,
            'company'                        => $company,
            'set_warehouse'                  => $defaultWarehouse,
            'pos_class'                      => $transaction->pos_class,
            'posting_date'                   => $transaction->created_at->format('Y-m-d'),
            'posting_time'                   => $transaction->created_at->format('H:i:s'),
            'set_posting_time'               => 1,
            'customer'                       => $transaction->customer?->erp_customer_name
                                                ?? \App\Models\Setting::get('erpnext_walkin_customer', 'Walk-in Customer'),
            'items'                          => $items,
            'payments'                       => $payments,
            // apply_discount_on = 'Net Total' agar % dihitung dari net (setelah diskon item)
            'apply_discount_on'              => 'Net Total',
            'additional_discount_percentage' => (float) $transaction->discount_percent,
            'discount_amount'                => (float) $transaction->discount_amount,
        ];

        if ($priceList) {
            $payload['selling_price_list'] = $priceList;
        }

        if ($transaction->tax_amount > 0) {
            $payload['taxes'] = [[
                'charge_type'  => 'Actual',
                'account_head' => 'Tax Collected - ' . substr($company, 0, 5),
                'description'  => 'Tax',
                'tax_amount'   => (float) $transaction->tax_amount,
            ]];
        }

        return $payload;
    }

    private function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash'     => 'Cash',
            'card'     => 'Credit Card',
            'transfer' => 'Bank Transfer',
            'qris'     => 'QRIS',
            default    => 'Cash',
        };
    }

    private function submitDoc(string $doctype, string $name): void
    {
        $this->client->put("/api/resource/{$doctype}/{$name}", [
            'json' => ['docstatus' => 1]
        ]);
    }

    // =========================================================
    // PULL PRODUCTS FROM ERPNext
    // =========================================================
    public function pullProducts(int $limit = 100, int $start = 0): array
    {
        try {
            $fields  = '["name","item_name","item_code","description","item_group","standard_rate","valuation_rate","stock_uom","is_sales_item","disabled","image"]';
            $filters = '[["is_sales_item","=",1],["disabled","=",0]]';

            $response = $this->client->get('/api/resource/Item', [
                'timeout' => 120,
                'query'   => [
                    'fields'      => $fields,
                    'filters'     => $filters,
                    'limit'       => $limit,
                    'limit_start' => $start,
                ]
            ]);

            $data  = json_decode($response->getBody()->getContents(), true);
            $items = $data['data'] ?? $data['message'] ?? [];

            // Overlay harga dari Price List jika dikonfigurasi
            $priceList = \App\Models\Setting::get('erpnext_price_list', '');
            if ($priceList && count($items) > 0) {
                $itemCodes = array_column($items, 'name');
                $priceMap  = $this->fetchItemPricesMap($itemCodes, $priceList);

                foreach ($items as &$item) {
                    if (isset($priceMap[$item['name']])) {
                        $item['standard_rate'] = $priceMap[$item['name']];
                    }
                }
                unset($item);
            }

            return ['success' => true, 'data' => $items];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // DOWNLOAD PRODUCT IMAGE FROM ERPNext → local public/images/products/
    // =========================================================
    public function downloadProductImage(string $erpImagePath, string $itemCode): ?string
    {
        try {
            $dir = public_path('images/products');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $ext      = strtolower(pathinfo($erpImagePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $filename = Str::slug($itemCode) . '.' . $ext;
            $dest     = $dir . DIRECTORY_SEPARATOR . $filename;

            $response = $this->client->get($erpImagePath, ['stream' => false]);
            file_put_contents($dest, $response->getBody()->getContents());

            return '/images/products/' . $filename;

        } catch (\Exception $e) {
            Log::warning("Failed to download product image '{$erpImagePath}' for item '{$itemCode}': " . $e->getMessage());
            return null;
        }
    }

    // =========================================================
    // FETCH ITEM PRICES FROM PRICE LIST
    // =========================================================
    private function fetchItemPricesMap(array $itemCodes, string $priceList): array
    {
        try {
            $filters = json_encode([
                ['price_list', '=', $priceList],
                ['item_code', 'in', $itemCodes],
                ['selling', '=', 1],
            ]);

            $response = $this->client->get('/api/resource/Item Price', [
                'query' => [
                    'fields'  => json_encode(['item_code', 'price_list_rate']),
                    'filters' => $filters,
                    'limit'   => count($itemCodes),
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $map  = [];
            foreach ($data['data'] ?? [] as $row) {
                // Simpan harga tertinggi jika ada duplikasi (misal valid_from berbeda)
                $code = $row['item_code'];
                if (!isset($map[$code]) || $row['price_list_rate'] > $map[$code]) {
                    $map[$code] = (float) $row['price_list_rate'];
                }
            }
            return $map;

        } catch (\Exception $e) {
            Log::warning("Failed to fetch Item Prices from price list '{$priceList}': " . $e->getMessage());
            return [];
        }
    }

    // =========================================================
    // PULL CUSTOMERS FROM ERPNext
    // =========================================================
    public function pullCustomers(int $limit = 100, int $start = 0): array
    {
        try {
            $response = $this->client->get('/api/resource/Customer', [
                'query' => [
                    'fields'      => json_encode([
                        'name', 'customer_name', 'customer_type',
                        'email_id', 'mobile_no', 'disabled'
                    ]),
                    'filters'     => json_encode([['disabled', '=', 0]]),
                    'limit'       => $limit,
                    'limit_start' => $start,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data['data'] ?? $data['message'] ?? []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // PUSH CUSTOMER TO ERPNext
    // =========================================================
    public function pushCustomer(Customer $customer): array
    {
        $posClass = \App\Models\Setting::get('pos_class', '');

        $payload = [
            'doctype'        => 'Customer',
            'customer_name'  => $customer->name,
            'customer_type'  => 'Individual',
            'customer_group' => 'Retail',
            'territory'      => 'Indonesia',
            'mobile_no'      => $customer->phone,
            'email_id'       => $customer->email,
        ];

        if ($posClass !== '') {
            $payload['class'] = $posClass;
        }

        try {
            $response = $this->client->post('/api/resource/Customer', ['json' => $payload]);
            $data     = json_decode($response->getBody()->getContents(), true);
            $docname  = $data['data']['name'] ?? null;

            $customer->update([
                'erp_customer_name' => $docname,
                'erp_last_sync'     => now(),
            ]);

            $this->logSync('customer', $customer->id, $customer->code, 'success', $payload, $data, $docname);
            return ['success' => true, 'docname' => $docname];

        } catch (RequestException $e) {
            $error = $this->extractError($e);
            $this->logSync('customer', $customer->id, $customer->code, 'failed', $payload, null, null, $error);
            return ['success' => false, 'error' => $error];
        }
    }

    // =========================================================
    // BULK SYNC PENDING TRANSACTIONS
    // =========================================================
    public function syncPendingTransactions(): array
    {
        $pending = Transaction::where('erp_sync_status', 'pending')
            ->where('status', 'completed')
            ->with(['items.product', 'customer'])
            ->latest()->get();

        $results = ['total' => $pending->count(), 'success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($pending as $transaction) {
            $result = $this->syncTransaction($transaction);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'invoice' => $transaction->invoice_no,
                    'error'   => $result['error']
                ];
            }
        }

        return $results;
    }

    // =========================================================
    // PULL STOCK FROM BIN (filtered by warehouse name)
    // =========================================================
    public function pullStockFromBin(string $warehouseName): array
    {
        try {
            $response = $this->client->get('/api/resource/Bin', [
                'timeout' => 120,
                'query'   => [
                    'fields'            => json_encode(['item_code', 'warehouse', 'actual_qty']),
                    'filters'           => json_encode([['warehouse', '=', $warehouseName]]),
                    'limit_page_length' => 0,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return ['success' => true, 'data' => $data['data'] ?? []];

        } catch (RequestException $e) {
            return ['success' => false, 'error' => $this->extractError($e)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // STOCK OPNAME — Material Issue (actual berlebih dari sistem)
    // =========================================================
    public function createOpnameMaterialIssue(string $warehouseName, string $opnameDate, array $items): array
    {
        return $this->createOpnameStockEntry('Material Issue', $warehouseName, $opnameDate, $items, 's_warehouse');
    }

    // =========================================================
    // STOCK OPNAME — Material Receipt (actual kurang dari sistem)
    // =========================================================
    public function createOpnameMaterialReceipt(string $warehouseName, string $opnameDate, array $items): array
    {
        return $this->createOpnameStockEntry('Material Receipt', $warehouseName, $opnameDate, $items, 't_warehouse');
    }

    private function createOpnameStockEntry(string $type, string $warehouseName, string $opnameDate, array $items, string $warehouseField): array
    {
        try {
            $entryItems = array_map(fn($item) => [
                'item_code'      => $item['item_code'],
                'qty'            => abs($item['qty']),
                'basic_rate'     => $item['basic_rate'] ?? 0,
                $warehouseField  => $warehouseName,
            ], $items);

            $response = $this->client->post('/api/resource/Stock Entry', [
                'json' => [
                    'stock_entry_type' => $type,
                    'purpose'          => $type,
                    'posting_date'     => $opnameDate,
                    'items'            => $entryItems,
                    'remarks'          => 'Stock Opname - ' . $opnameDate,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $name = $data['data']['name'] ?? null;

            if (!$name) {
                return ['success' => false, 'error' => 'Stock Entry dibuat tapi name tidak ada di response'];
            }

            $this->submitDoc('Stock Entry', $name);

            return ['success' => true, 'name' => $name];

        } catch (RequestException $e) {
            return ['success' => false, 'error' => $this->extractError($e)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // GET WAREHOUSES FROM ERPNext
    // =========================================================
    public function getWarehouses(): array
    {
        try {
            $company = \App\Models\Setting::get('erpnext_company', env('ERPNEXT_COMPANY'));
            $filters = $company
                ? json_encode([['company', '=', $company], ['disabled', '=', 0]])
                : json_encode([['disabled', '=', 0]]);

            $response = $this->client->get('/api/resource/Warehouse', [
                'query' => [
                    'fields'  => json_encode(['name', 'warehouse_name', 'warehouse_type', 'is_group', 'company', 'parent_warehouse']),
                    'filters' => $filters,
                    'limit'   => 200,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data['data'] ?? []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    // =========================================================
    // GET PENDING IN-TRANSIT STOCK ENTRIES FROM ERPNext
    // =========================================================
    public function getPendingInTransitEntries(): array
    {
        try {
            $filters = json_encode([
                ['purpose', '=', 'Material Transfer'],
                ['docstatus', '=', 1],
            ]);

            $response = $this->client->get('/api/resource/Stock Entry', [
                'query' => [
                    'fields'  => json_encode([
                        'name', 'posting_date', 'posting_time',
                        'from_warehouse', 'to_warehouse', 'remarks',
                    ]),
                    'filters' => $filters,
                    'limit'   => 100,
                    'order_by' => 'posting_date desc',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data['data'] ?? []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    // =========================================================
    // GET STOCK ENTRY DETAIL FROM ERPNext
    // =========================================================
    public function getStockEntryDetail(string $name): array
    {
        try {
            $response = $this->client->get("/api/resource/Stock Entry/{$name}");
            $data     = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data['data'] ?? []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================
    // CREATE OUTGOING TRANSFER → ERPNext (Material Transfer to In-Transit)
    // =========================================================
    public function createOutgoingTransfer(StockTransfer $transfer): array
    {
        $payload = [
            'doctype'           => 'Stock Entry',
            'stock_entry_type'  => 'Material Transfer',
            'purpose'           => 'Material Transfer',
            'company'           => \App\Models\Setting::get('erpnext_company', env('ERPNEXT_COMPANY')),
            'posting_date'      => now()->format('Y-m-d'),
            'posting_time'      => now()->format('H:i:s'),
            'set_posting_time'  => 1,
            'remarks'           => $transfer->notes,
            'items'             => $transfer->items->map(function ($item) use ($transfer) {
                return [
                    'item_code'   => $item->item_code,
                    'item_name'   => $item->item_name,
                    'qty'         => $item->quantity,
                    'uom'         => $item->unit,
                    's_warehouse' => $transfer->from_warehouse,
                    't_warehouse' => $transfer->to_warehouse,
                ];
            })->toArray(),
        ];

        try {
            $response = $this->client->post('/api/resource/Stock Entry', ['json' => $payload]);
            $data     = json_decode($response->getBody()->getContents(), true);
            $docname  = $data['data']['name'] ?? null;

            if ($docname) {
                $this->submitDoc('Stock Entry', $docname);
            }

            $transfer->update([
                'erp_stock_entry' => $docname,
                'erp_sync_status' => 'synced',
                'erp_sync_error'  => null,
                'status'          => 'submitted',
                'submitted_at'    => now(),
            ]);

            // Kurangi stok dari warehouse pengirim
            $fromWarehouse = Warehouse::where('name', $transfer->from_warehouse)->first();
            if ($fromWarehouse) {
                foreach ($transfer->items as $item) {
                    if ($item->product_id) {
                        ProductStock::forProductWarehouse($item->product_id, $fromWarehouse->id)
                            ->decrementQty($item->quantity);
                        $item->product->decrement('stock', $item->quantity);
                    }
                }
            }

            $this->logSync('stock_transfer', $transfer->id, $transfer->transfer_no,
                'success', $payload, $data, $docname);

            return ['success' => true, 'docname' => $docname];

        } catch (RequestException $e) {
            $error = $this->extractError($e);

            $transfer->update(['erp_sync_status' => 'failed', 'erp_sync_error' => $error]);
            $this->logSync('stock_transfer', $transfer->id, $transfer->transfer_no,
                'failed', $payload, null, null, $error);

            return ['success' => false, 'error' => $error];
        }
    }

    // =========================================================
    // CREATE INCOMING RECEIPT → ERPNext (Material Transfer for Receive)
    // =========================================================
    public function createIncomingReceipt(StockTransfer $transfer): array
    {
        $payload = [
            'doctype'           => 'Stock Entry',
            'stock_entry_type'  => 'Material Transfer',
            'purpose'           => 'Material Transfer',
            'company'           => \App\Models\Setting::get('erpnext_company', env('ERPNEXT_COMPANY')),
            'posting_date'      => now()->format('Y-m-d'),
            'posting_time'      => now()->format('H:i:s'),
            'set_posting_time'  => 1,
            'remarks'           => $transfer->notes,
            'items'             => $transfer->items->map(function ($item) use ($transfer) {
                return [
                    'item_code'   => $item->item_code,
                    'item_name'   => $item->item_name,
                    'qty'         => $item->actual_quantity ?? $item->quantity,
                    'uom'         => $item->unit,
                    's_warehouse' => $transfer->from_warehouse,
                    't_warehouse' => $transfer->to_warehouse,
                ];
            })->toArray(),
        ];

        try {
            $response = $this->client->post('/api/resource/Stock Entry', ['json' => $payload]);
            $data     = json_decode($response->getBody()->getContents(), true);
            $docname  = $data['data']['name'] ?? null;

            if ($docname) {
                $this->submitDoc('Stock Entry', $docname);
            }

            $transfer->update([
                'erp_stock_entry' => $docname,
                'erp_sync_status' => 'synced',
                'erp_sync_error'  => null,
                'status'          => 'submitted',
                'submitted_at'    => now(),
            ]);

            // Update stok lokal per warehouse
            $toWarehouse = Warehouse::where('name', $transfer->to_warehouse)->first();
            foreach ($transfer->items as $item) {
                if ($item->product_id) {
                    $qty = $item->actual_quantity ?? $item->quantity;
                    $item->product->increment('stock', $qty);
                    if ($toWarehouse) {
                        ProductStock::forProductWarehouse($item->product_id, $toWarehouse->id)
                            ->incrementQty($qty);
                    }
                }
            }

            $this->logSync('stock_transfer', $transfer->id, $transfer->transfer_no,
                'success', $payload, $data, $docname);

            return ['success' => true, 'docname' => $docname];

        } catch (RequestException $e) {
            $error = $this->extractError($e);

            $transfer->update(['erp_sync_status' => 'failed', 'erp_sync_error' => $error]);
            $this->logSync('stock_transfer', $transfer->id, $transfer->transfer_no,
                'failed', $payload, null, null, $error);

            return ['success' => false, 'error' => $error];
        }
    }

    // =========================================================
    // VERIFY SYSTEM MANAGER (untuk Factory Reset)
    // =========================================================
    public function verifySystemManager(string $username, string $password): array
    {
        if (empty($this->baseUrl)) {
            return ['success' => false, 'error' => 'ERP HPY URL belum dikonfigurasi.'];
        }

        try {
            // Gunakan cookie jar agar session ERPNext terbawa ke request berikutnya
            $jar    = new CookieJar();
            $client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout'  => 15,
                'verify'   => false,
                'cookies'  => $jar,
            ]);

            // Step 1: Login ke ERPNext
            $loginResp = $client->post('/api/method/login', [
                'json' => ['usr' => $username, 'pwd' => $password],
            ]);

            $loginData = json_decode($loginResp->getBody()->getContents(), true);

            if (($loginData['message'] ?? '') !== 'Logged In') {
                return ['success' => false, 'error' => 'Login gagal. Username atau password salah.'];
            }

            $fullname = $loginData['full_name'] ?? $username;

            // Step 2: Ambil dokumen User (user selalu bisa baca dokumen dirinya sendiri)
            // Roles ada sebagai child table di dalam dokumen User
            $userResp = $client->get('/api/resource/User/' . urlencode($username));
            $userData = json_decode($userResp->getBody()->getContents(), true);
            $roles    = $userData['data']['roles'] ?? [];
            $hasRole  = collect($roles)->contains('role', 'System Manager');

            if (!$hasRole) {
                return [
                    'success' => false,
                    'error'   => "User '{$username}' tidak memiliki role System Manager di ERP HPY.",
                ];
            }

            return ['success' => true, 'username' => $username, 'fullname' => $fullname];

        } catch (RequestException $e) {
            // ERPNext mengembalikan 401 untuk kredensial salah
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                return ['success' => false, 'error' => 'Login gagal. Username atau password salah.'];
            }
            return ['success' => false, 'error' => 'Koneksi ERP HPY gagal: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    private function extractError(RequestException $e): string
    {
        if (!$e->hasResponse()) {
            return $e->getMessage();
        }

        $status = $e->getResponse()->getStatusCode();
        $body   = $e->getResponse()->getBody()->getContents();

        // HTML response (maintenance page, nginx error, etc.)
        if (str_starts_with(ltrim($body), '<')) {
            return "Server ERP HPY tidak tersedia (HTTP {$status}). Coba beberapa saat lagi.";
        }

        return $body ?: $e->getMessage();
    }

    private function logSync(
        string $type, int $refId, ?string $refNo,
        string $status, $request, $response, ?string $docname, ?string $error = null
    ): void {
        ErpSyncLog::create([
            'type'             => $type,
            'reference_id'     => $refId,
            'reference_no'     => $refNo,
            'status'           => $status,
            'request_payload'  => json_encode($request),
            'response_payload' => json_encode($response),
            'erp_docname'      => $docname,
            'error_message'    => $error,
        ]);
    }
}