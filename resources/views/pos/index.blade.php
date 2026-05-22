<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir — HappyPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto+Mono:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --blue: #4285F4; --red: #EA4335; --yellow: #FBBC05; --green: #34A853;
            --blue-dark: #1967D2; --blue-light: #E8F0FE;
            --bg: #F8F9FA; --surface: #FFFFFF; --surface2: #F1F3F4;
            --border: #DADCE0; --text: #202124; --text2: #5F6368; --text3: #80868B;
            --shadow-sm: 0 1px 3px rgba(60,64,67,.15),0 1px 2px rgba(60,64,67,.1);
            --shadow: 0 2px 6px 2px rgba(60,64,67,.15),0 1px 2px rgba(60,64,67,.2);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

        /* TOP BAR */
        .pos-topbar {
            height: 52px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 16px; gap: 16px; flex-shrink: 0;
        }
        .pos-topbar-brand { font-family:'Google Sans',sans-serif; font-weight:700; font-size:18px; display:flex; align-items:center; gap:8px; text-decoration:none; color:var(--text); }
        .pos-topbar-brand span:nth-child(2) { color:#4285F4; }
        .pos-topbar-brand span:nth-child(3) { color:#EA4335; }
        .pos-topbar-brand span:nth-child(4) { color:#FBBC05; }
        .pos-topbar-brand span:nth-child(5) { color:#34A853; }
        .topbar-actions { margin-left:auto; display:flex; align-items:center; gap:8px; }

        /* MAIN POS LAYOUT */
        .pos-layout { display: flex; flex: 1; overflow: hidden; }

        /* LEFT: Products Panel */
        .pos-products { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--bg); }

        /* Search & Filter */
        .pos-search-bar { padding: 12px 16px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; gap: 10px; }
        .search-input-wrap { flex: 1; position: relative; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text3); }
        .search-input { width: 100%; padding: 10px 12px 10px 38px; border: 1px solid var(--border); border-radius: 24px; font-size: 14px; background: var(--surface2); transition: all .2s; }
        .search-input:focus { outline: none; border-color: var(--blue); background: var(--surface); box-shadow: 0 0 0 3px rgba(66,133,244,.12); }

        /* Categories */
        .categories-bar { display: flex; gap: 8px; padding: 12px 16px; background: var(--surface); border-bottom: 1px solid var(--border); overflow-x: auto; flex-shrink: 0; }
        .categories-bar::-webkit-scrollbar { height: 4px; }
        .categories-bar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
        .cat-btn { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; white-space: nowrap; transition: all .2s; font-family:'Google Sans',sans-serif; }
        .cat-btn.all { background: var(--blue); color: #fff; }
        .cat-btn:not(.all) { background: var(--surface2); color: var(--text2); border: 1px solid var(--border); }
        .cat-btn:not(.all):hover { background: var(--blue-light); color: var(--blue); border-color: var(--blue); }
        .cat-btn.active { color: #fff; border-color: transparent; }

        /* Products Grid */
        /* .products-grid { flex: 1; overflow-y: auto; padding: 16px; display: grid; grid-template-columns: repeat(auto-fill, minmax(165px, 1fr)); gap: 12px; align-content: start; } */
        .products-grid { flex: 1; overflow-y: auto; padding: 16px; display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; align-content: start; }
        .product-card {
            background: var(--surface); border-radius: 12px; border: 2px solid var(--border);
            cursor: pointer; transition: all .2s; overflow: hidden; position: relative;
            display: flex; flex-direction: column;
        }
        .product-card:hover { box-shadow: var(--shadow); border-color: var(--blue); transform: translateY(-2px); }
        .product-card.out-of-stock { opacity: .5; cursor: not-allowed; }
        .product-card.out-of-stock:hover { transform: none; box-shadow: none; border-color: var(--border); }
        .product-img {
            height: 100px; background: var(--surface2); display: flex; align-items: center;
            justify-content: center; font-size: 40px; position: relative;
        }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-cat-dot { position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; border-radius: 50%; }
        .product-info { padding: 10px 12px 12px; }
        .product-name { 
            font-size: 13px; font-weight: 600; line-height: 1.4; 
            margin-bottom: 6px; 
            min-height: 36px;
            display: -webkit-box; -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; overflow: hidden; 
        }
        .product-price { font-family: 'Google Sans', sans-serif; font-size: 14px; font-weight: 700; color: var(--blue); }
        .product-stock { font-size: 11px; color: var(--text3); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .stock-low { color: #E37400; font-weight: 600; }
        .low-stock-badge { position: absolute; top: 8px; left: 8px; background: #FBBC05; color: #202124; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }

        /* RIGHT: Cart Panel */
        .pos-cart { width: 360px; background: var(--surface); border-left: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }

        /* Customer select */
        .cart-customer { padding: 12px 16px; border-bottom: 1px solid var(--border); }
        .customer-select-btn { width: 100%; padding: 8px 12px; border: 1px dashed var(--border); border-radius: 8px; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text3); transition: all .2s; }
        .customer-select-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-light); }
        .customer-select-btn.has-customer { border-style: solid; border-color: var(--green); color: var(--text); background: #E6F4EA; }

        /* Cart Items */
        .cart-items { flex: 1; overflow-y: auto; padding: 8px 0; }
        .cart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text3); gap: 12px; }
        .cart-empty-icon { font-size: 48px; opacity: .3; }
        .cart-empty-text { font-size: 14px; text-align: center; }

        .cart-item { padding: 10px 16px; display: flex; gap: 10px; align-items: flex-start; border-bottom: 1px solid var(--surface2); transition: background .15s; }
        .cart-item:hover { background: var(--surface2); }
        .cart-item-info { flex: 1 }
        .cart-item-name { font-size: 13px; font-weight: 600; line-height: 1.3; }
        .cart-item-price { font-size: 12px; color: var(--text3); margin-top: 2px; }
        .cart-item-controls { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
        .qty-btn { width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--border); background: var(--surface); cursor: pointer; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; transition: all .15s; color: var(--text); }
        .qty-btn:hover { background: var(--blue); color: #fff; border-color: var(--blue); }
        .qty-input { width: 36px; text-align: center; border: 1px solid var(--border); border-radius: 6px; padding: 2px 4px; font-size: 14px; font-weight: 700; font-family: 'Roboto Mono', monospace; }
        .cart-item-subtotal { font-family:'Google Sans',sans-serif; font-size:14px; font-weight:700; color:var(--blue); white-space:nowrap; }
        .cart-item-remove { color: var(--text3); cursor: pointer; font-size: 14px; padding: 4px; transition: color .15s; }
        .cart-item-remove:hover { color: var(--red); }

        /* Cart Summary */
        .cart-summary { border-top: 2px solid var(--border); padding: 16px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; }
        .summary-row.total { font-family:'Google Sans',sans-serif; font-size: 20px; font-weight: 700; color: var(--text); border-top: 1px solid var(--border); padding-top: 10px; margin-top: 6px; margin-bottom: 0; }
        .discount-row { display: flex; gap: 8px; margin-bottom: 12px; }
        .discount-input { flex: 1; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; }

        /* Payment */
        .cart-payment { padding: 12px 16px; border-top: 1px solid var(--border); }
        .payment-methods { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 12px; }
        .pay-btn { padding: 8px 4px; border: 2px solid var(--border); border-radius: 8px; background: var(--surface2); cursor: pointer; text-align: center; font-size: 11px; font-weight: 700; color: var(--text2); transition: all .2s; }
        .pay-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-light); }
        .pay-btn.active { border-color: var(--blue); background: var(--blue); color: #fff; }
        .pay-icon { font-size: 16px; display: block; margin-bottom: 3px; }
        .paid-row { display: flex; gap: 8px; margin-bottom: 10px; }
        .paid-input { flex: 1; padding: 10px 14px; border: 2px solid var(--blue); border-radius: 8px; font-size: 16px; font-weight: 700; font-family:'Roboto Mono',monospace; text-align: right; }
        .change-display { background: var(--surface2); border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .change-label { font-size: 13px; color: var(--text3); font-weight: 500; }
        .change-value { font-family:'Google Sans',sans-serif; font-size: 18px; font-weight: 700; color: var(--green); }
        .btn-checkout { width: 100%; padding: 14px; background: var(--green); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; font-family:'Google Sans',sans-serif; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s; }
        .btn-checkout:hover { background: #2D9247; box-shadow: 0 4px 12px rgba(52,168,83,.4); }
        .btn-checkout:disabled { background: var(--text3); cursor: not-allowed; box-shadow: none; }
        .btn-clear { width: 100%; padding: 8px; background: transparent; color: var(--red); border: 1px solid var(--red); border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; margin-bottom: 8px; transition: all .2s; }
        .btn-clear:hover { background: #FCE8E6; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: var(--surface); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.2); max-width: 480px; width: 90%; max-height: 90vh; overflow-y: auto; animation: modalIn .2s ease; }
        @keyframes modalIn { from{opacity:0;transform:scale(.95)}to{opacity:1;transform:none} }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-family:'Google Sans',sans-serif; font-size:18px; font-weight:700; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; gap: 8px; justify-content: flex-end; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 24px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all .2s; font-family:'Google Sans',sans-serif; }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-success { background: var(--green); color: #fff; }
        .btn-ghost { background: transparent; color: var(--text2); border: 1px solid var(--border); }
        .form-control { width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);margin-bottom:12px; }
        .form-control:focus { outline:none;border-color:var(--blue); }
        .form-label { display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px; }

        /* Receipt Modal */
        .receipt { font-family:'Roboto Mono',monospace; font-size:13px; max-width:300px; margin:0 auto; }
        .receipt-header { text-align:center; margin-bottom:12px; }
        .receipt-title { font-size:18px; font-weight:700; font-family:'Google Sans',sans-serif; }
        .receipt-divider { border:none; border-top: 1px dashed var(--border); margin: 8px 0; }
        .receipt-row { display:flex; justify-content:space-between; margin:4px 0; }
        .receipt-total { font-size:16px; font-weight:700; }

        /* Toast */
        #toasts { position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px; }
        .toast { background:#202124;color:#fff;padding:12px 18px;border-radius:8px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.3);display:flex;align-items:center;gap:8px;animation:toastIn .3s ease;min-width:260px; }
        .toast.ok { background:#34A853; }
        .toast.err { background:#EA4335; }
        @keyframes toastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:none}}

        .spinner { width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:inline-block; }
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
<!-- Topbar -->
<div class="pos-topbar">
    <a href="{{ route('dashboard') }}" class="pos-topbar-brand">
    <img src="/images/happypos.png" alt="HappyPos"
        style="height:34px;width:auto;object-fit:contain;">
    </a>
    <span style="font-size:13px;color:var(--text3)"><i class="fas fa-cash-register"></i> Kasir: <strong>{{ auth()->user()->name }}</strong></span>
    <span id="clock" style="font-family:'Roboto Mono',monospace;font-size:13px;color:var(--text3);margin-left:12px"></span>
    <div class="topbar-actions">
        <a href="{{ route('transactions.index') }}" class="btn btn-ghost" style="padding:6px 14px;border:1px solid var(--border);border-radius:20px;font-size:13px;text-decoration:none;color:var(--text2);display:flex;align-items:center;gap:6px;"><i class="fas fa-history"></i> Riwayat</a>
        <a href="{{ route('dashboard') }}" class="btn btn-ghost" style="padding:6px 14px;border:1px solid var(--border);border-radius:20px;font-size:13px;text-decoration:none;color:var(--text2);display:flex;align-items:center;gap:6px;"><i class="fas fa-th-large"></i> Dashboard</a>
    </div>
</div>

<!-- Main POS Layout -->
<div class="pos-layout">
    <!-- LEFT: Products -->
    <div class="pos-products">
        <!-- Search Bar -->
        <div class="pos-search-bar">
            <div class="search-input-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Cari produk atau scan barcode... (F3)" autocomplete="off">
            </div>
        </div>

        <!-- Categories -->
        <div class="categories-bar">
            <button class="cat-btn all active" onclick="filterCategory(null, this)">
                Semua
            </button>
            @foreach($categories as $cat)
            <button class="cat-btn" style="--cat-color:{{ $cat->color }}"
                onclick="filterCategory({{ $cat->id }}, this)"
                data-id="{{ $cat->id }}">
                {{ $cat->name }}
            </button>
            @endforeach
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
        <div id="loadingProducts" style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text3)">
            <div style="font-size:32px;margin-bottom:8px">⏳</div>
            <div style="font-size:14px">Memuat produk...</div>
        </div>
    </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="pos-cart">
        <!-- Customer -->
        <div class="cart-customer">
            <button class="customer-select-btn" id="customerBtn" onclick="openCustomerModal()">
                <i class="fas fa-user-circle"></i>
                <span id="customerBtnText">Pilih Customer (opsional)</span>
                <i class="fas fa-times" id="customerClearBtn" style="display:none;margin-left:auto;color:#EA4335" onclick="clearCustomer(event)"></i>
            </button>
        </div>

        <!-- Cart Items -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <div class="cart-empty-icon">🛒</div>
                <div class="cart-empty-text">Keranjang kosong<br><small>Klik produk untuk menambahkan</small></div>
            </div>
        </div>

        <!-- Summary -->
        <div class="cart-summary">
            <div class="summary-row">
                <span style="color:var(--text2)">Subtotal</span>
                <span id="subtotalDisplay">Rp 0</span>
            </div>
            <div class="discount-row">
                <div style="flex:1">
                    <div style="font-size:11px;color:var(--text3);margin-bottom:3px">Diskon (Rp)</div>
                    <input type="number" id="discountAmt" class="discount-input" placeholder="0" min="0" oninput="recalculate()">
                </div>
                <div style="flex:1">
                    <div style="font-size:11px;color:var(--text3);margin-bottom:3px">Diskon (%)</div>
                    <input type="number" id="discountPct" class="discount-input" placeholder="0" min="0" max="100" oninput="recalculate()">
                </div>
            </div>
            <div class="summary-row">
                <span style="color:var(--text2)">Diskon</span>
                <span id="discountDisplay" style="color:#EA4335">- Rp 0</span>
            </div>
            <div class="summary-row">
                <span style="color:var(--text2)">Pajak</span>
                <span id="taxDisplay">Rp 0</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="totalDisplay">Rp 0</span>
            </div>
        </div>

        <!-- Payment -->
        <div class="cart-payment">
            <div class="payment-methods">
                <button class="pay-btn active" data-method="cash" onclick="selectPayment(this)">
                    <span class="pay-icon">💵</span>Tunai
                </button>
                <button class="pay-btn" data-method="card" onclick="selectPayment(this)">
                    <span class="pay-icon">💳</span>Kartu
                </button>
                <button class="pay-btn" data-method="transfer" onclick="selectPayment(this)">
                    <span class="pay-icon">🏦</span>Transfer
                </button>
                <button class="pay-btn" data-method="qris" onclick="selectPayment(this)">
                    <span class="pay-icon">📱</span>QRIS
                </button>
            </div>

            <div id="cashSection">
                <div style="font-size:12px;color:var(--text3);margin-bottom:4px;font-weight:600">NOMINAL BAYAR</div>
                <div class="paid-row">
                    <input type="number" id="paidAmount" class="paid-input" placeholder="0" oninput="calcChange()">
                </div>
                <!-- Quick amounts -->
                <div id="quickAmounts" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px"></div>
                <div class="change-display">
                    <span class="change-label"><i class="fas fa-exchange-alt"></i> Kembalian</span>
                    <span class="change-value" id="changeDisplay">Rp 0</span>
                </div>
            </div>

            <div style="margin-bottom:8px;">
                <div style="font-size:11px;color:var(--text3);margin-bottom:3px;font-weight:600;">POS CLASS <span style="font-weight:400;opacity:.7">(opsional)</span></div>
                <input type="text" id="posClass" placeholder="Masukkan POS Class..."
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);background:var(--surface);">
            </div>

            <button class="btn-clear" onclick="clearCart()"><i class="fas fa-trash"></i> Hapus Semua</button>
            <button class="btn-checkout" id="checkoutBtn" onclick="processCheckout()" disabled>
                <i class="fas fa-check-circle"></i>
                <span id="checkoutBtnText">Bayar</span>
            </button>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal-overlay" id="customerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-users" style="color:#4285F4"></i> Pilih Customer</div>
            <button onclick="closeModal('customerModal')" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text3)">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" id="customerSearch" class="form-control" placeholder="Cari nama, telp, atau kode customer..." oninput="searchCustomers(this.value)" autofocus>
            <div id="customerList" style="max-height:280px;overflow-y:auto;margin-top:4px"></div>
            <hr style="margin:16px 0;border:none;border-top:1px dashed var(--border)">
            <div style="font-size:13px;color:var(--text3);margin-bottom:8px;font-weight:600"><i class="fas fa-plus-circle" style="color:#34A853"></i> Customer Baru</div>
            <input type="text" id="newCustName" class="form-control" placeholder="Nama customer">
            <input type="text" id="newCustPhone" class="form-control" placeholder="Nomor telepon (opsional)">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('customerModal')">Batal</button>
            <button class="btn btn-success" onclick="addNewCustomer()"><i class="fas fa-user-plus"></i> Tambah Customer Baru</button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" style="color:#34A853"><i class="fas fa-check-circle"></i> Transaksi Berhasil!</div>
        </div>
        <div class="modal-body">
            <div class="receipt" id="receiptContent"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeReceiptAndReset()"><i class="fas fa-times"></i> Tutup</button>
            <button class="btn btn-primary" onclick="printReceipt()"><i class="fas fa-print"></i> Cetak Struk</button>
            <button class="btn btn-success" onclick="closeReceiptAndReset()"><i class="fas fa-plus"></i> Transaksi Baru</button>
        </div>
    </div>
</div>

<div id="toasts"></div>

<script>
    // ============================================================
// STATE
// ============================================================
let cart = [];
let selectedCustomer = null;
let selectedPayment = 'cash';
let allProducts = [];
let currentCategoryFilter = null;
let lastReceipt = null;
const csrf = document.querySelector('meta[name="csrf-token"]').content;

// ============================================================
// CLOCK
// ============================================================
function updateClock() {
    document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID');
}
setInterval(updateClock, 1000); updateClock();

// ============================================================
// LOAD & RENDER PRODUCTS
// ============================================================
async function loadProducts(categoryId = null) {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">⏳</div><div>Memuat produk...</div></div>';
    const url = `/pos/search-products?q=${categoryId ? '&category_id='+categoryId : ''}`;
    const resp = await fetch(url, { headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf} });
    const products = await resp.json();
    allProducts = products;
    renderProducts(products);
}

function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    if (products.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">📦</div><div>Tidak ada produk</div></div>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="product-card ${!p.track_stock || p.stock > 0 ? '' : 'out-of-stock'}"
            data-id="${p.id}"
            data-name="${p.name.replace(/'/g,"&#39;")}"
            data-price="${p.price}"
            data-sku="${p.sku}"
            data-stock="${p.stock}"
            data-unit="${p.unit}"
            data-tax="${p.tax_rate}"
            data-track="${p.track_stock ? 1 : 0}"
            data-category="${p.category_id || ''}"
            onclick="addToCart(this)">
            <div class="product-img">
                <span>📦</span>
                <span class="product-cat-dot" style="background:${p.category_color || '#4285F4'}"></span>
            </div>
            <div class="product-info">
                <div class="product-name">${p.name}</div>
                <div class="product-price">Rp ${fmt(p.price)}</div>
                <div class="product-stock ${p.is_low_stock ? 'stock-low' : ''}">
                    ${p.track_stock ? (p.stock <= 0 ? 'Habis' : 'Stok: '+p.stock+' '+p.unit) : '∞ Tidak terbatas'}
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================================
// FILTER & SEARCH
// ============================================================
function filterCategory(catId, btn) {
    currentCategoryFilter = catId;
    document.querySelectorAll('.cat-btn').forEach(b => {
        b.classList.remove('active');
        b.style.background = ''; b.style.color = '';
    });
    btn.classList.add('active');
    btn.style.background = '#4285F4'; btn.style.color = '#fff';
    loadProducts(catId);
}

let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const term = this.value.trim();
        const url = `/pos/search-products?q=${encodeURIComponent(term)}${currentCategoryFilter ? '&category_id='+currentCategoryFilter : ''}`;
        const resp = await fetch(url, { headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf} });
        const products = await resp.json();
        allProducts = products;
        renderProducts(products);
    }, 300);
});

document.addEventListener('keydown', e => {
    if (e.key === 'F3') { e.preventDefault(); document.getElementById('searchInput').focus(); document.getElementById('searchInput').select(); }
    if (e.key === 'Escape') { closeModal('customerModal'); closeModal('receiptModal'); }
});

// ============================================================
// CART
// ============================================================
function addToCart(el) {
    const id = parseInt(el.dataset.id);
    const track = el.dataset.track === '1';
    const stock = parseInt(el.dataset.stock);

    if (track && stock <= 0) { toast('Stok habis!', 'err'); return; }

    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (track && existing.qty >= stock) { toast('Stok tidak cukup!', 'err'); return; }
        existing.qty++;
    } else {
        cart.push({
            id, name: el.dataset.name, price: parseFloat(el.dataset.price),
            sku: el.dataset.sku, stock, unit: el.dataset.unit,
            tax: parseFloat(el.dataset.tax), track,
            qty: 1, discount: 0
        });
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (item.track && newQty > item.stock) { toast('Stok tidak cukup!', 'err'); return; }
    item.qty = newQty;
    renderCart();
}

function setQty(id, val) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const qty = parseInt(val) || 1;
    if (item.track && qty > item.stock) { toast('Stok tidak cukup!', 'err'); return; }
    item.qty = Math.max(1, qty);
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    cart = [];
    document.getElementById('discountAmt').value = '';
    document.getElementById('discountPct').value = '';
    document.getElementById('posClass').value = '';
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('cartEmpty');

    if (cart.length === 0) {
        container.innerHTML = '';
        container.appendChild(empty);
        empty.style.display = 'flex';
        document.getElementById('checkoutBtn').disabled = true;
        recalculate();
        return;
    }

    empty.style.display = 'none';

    let html = '';
    cart.forEach(item => {
        const subtotal = item.price * item.qty;
        html += `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">Rp ${fmt(item.price)} × ${item.qty}</div>
                <div class="cart-item-controls">
                    <button class="qty-btn" onclick="updateQty(${item.id},-1)">−</button>
                    <input class="qty-input" type="number" value="${item.qty}" min="1"
                        onchange="setQty(${item.id},this.value)" style="width:36px">
                    <button class="qty-btn" onclick="updateQty(${item.id},1)">+</button>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                <i class="fas fa-times cart-item-remove" onclick="removeFromCart(${item.id})"></i>
                <span class="cart-item-subtotal">Rp ${fmt(subtotal)}</span>
            </div>
        </div>`;
    });

    container.querySelectorAll('.cart-item').forEach(el => el.remove());
    empty.insertAdjacentHTML('afterend', html);
    recalculate();
    document.getElementById('checkoutBtn').disabled = false;
}

function recalculate() {
    let subtotal = cart.reduce((s, i) => s + (i.price * i.qty), 0);
    let tax = cart.reduce((s, i) => s + (i.price * i.qty * (i.tax / 100)), 0);
    let discAmt = parseFloat(document.getElementById('discountAmt').value) || 0;
    let discPct = parseFloat(document.getElementById('discountPct').value) || 0;
    if (discPct > 0) discAmt = subtotal * (discPct / 100);
    const total = subtotal + tax - discAmt;

    document.getElementById('subtotalDisplay').textContent = 'Rp ' + fmt(subtotal);
    document.getElementById('discountDisplay').textContent = '- Rp ' + fmt(discAmt);
    document.getElementById('taxDisplay').textContent = 'Rp ' + fmt(tax);
    document.getElementById('totalDisplay').textContent = 'Rp ' + fmt(total);

    if (selectedPayment === 'cash') {
        const amounts = [total, Math.ceil(total/10000)*10000, Math.ceil(total/50000)*50000, Math.ceil(total/100000)*100000];
        const unique = [...new Set(amounts)].filter((v,i,a)=>a.indexOf(v)===i).slice(0,4);
        document.getElementById('quickAmounts').innerHTML = unique.map(a =>
            `<button onclick="setPaid(${a})" style="padding:4px 10px;border:1px solid #DADCE0;border-radius:16px;font-size:12px;cursor:pointer;background:#F1F3F4;font-weight:600">Rp ${fmt(a)}</button>`
        ).join('');
    }
    calcChange();
}

function calcChange() {
    const totalText = document.getElementById('totalDisplay').textContent;
    const total = parseFloat(totalText.replace(/[^0-9]/g,''));
    const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    const change = paid - total;
    const el = document.getElementById('changeDisplay');
    el.textContent = 'Rp ' + fmt(Math.max(0, change));
    el.style.color = change >= 0 ? '#34A853' : '#EA4335';
}

function setPaid(amount) {
    document.getElementById('paidAmount').value = amount;
    calcChange();
}

function selectPayment(btn) {
    document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedPayment = btn.dataset.method;
    document.getElementById('cashSection').style.display = selectedPayment === 'cash' ? 'block' : 'none';
}

// ============================================================
// CUSTOMER
// ============================================================
function openCustomerModal() {
    document.getElementById('customerModal').classList.add('show');
    document.getElementById('customerSearch').value = '';
    renderCustomerList(allCustomers);
    setTimeout(() => document.getElementById('customerSearch').focus(), 100);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

const allCustomers = @json($customers);

function renderCustomerList(list) {
    const el = document.getElementById('customerList');
    if (list.length === 0) {
        el.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text3);font-size:14px">Tidak ditemukan</div>';
        return;
    }
    el.innerHTML = list.slice(0,8).map(c => `
        <div onclick="selectCustomer(${c.id},'${c.name}','${c.code}')"
            style="padding:10px 12px;border-radius:8px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:background .15s"
            onmouseover="this.style.background='#F1F3F4'" onmouseout="this.style.background=''">
            <div>
                <div style="font-weight:600;font-size:14px">${c.name}</div>
                <div style="font-size:12px;color:var(--text3)">${c.code} ${c.phone ? '· ' + c.phone : ''}</div>
            </div>
            <div style="font-size:12px;color:#4285F4;font-weight:600">${c.loyalty_points > 0 ? '⭐ '+c.loyalty_points : ''}</div>
        </div>
    `).join('');
}

function searchCustomers(q) {
    const filtered = allCustomers.filter(c =>
        c.name.toLowerCase().includes(q.toLowerCase()) ||
        (c.phone && c.phone.includes(q)) ||
        c.code.toLowerCase().includes(q.toLowerCase())
    );
    renderCustomerList(filtered);
}

function selectCustomer(id, name, code) {
    selectedCustomer = { id, name, code };
    const btn = document.getElementById('customerBtn');
    btn.classList.add('has-customer');
    document.getElementById('customerBtnText').textContent = '👤 ' + name + ' (' + code + ')';
    document.getElementById('customerClearBtn').style.display = 'block';
    closeModal('customerModal');
}

function clearCustomer(e) {
    e.stopPropagation();
    selectedCustomer = null;
    const btn = document.getElementById('customerBtn');
    btn.classList.remove('has-customer');
    document.getElementById('customerBtnText').textContent = 'Pilih Customer (opsional)';
    document.getElementById('customerClearBtn').style.display = 'none';
}

async function addNewCustomer() {
    const name = document.getElementById('newCustName').value.trim();
    if (!name) { toast('Nama customer wajib diisi!', 'err'); return; }
    const phone = document.getElementById('newCustPhone').value.trim();
    const resp = await fetch('/customers', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
        body: JSON.stringify({ name, phone })
    });
    const data = await resp.json();
    if (data.success) {
        allCustomers.unshift(data.customer);
        selectCustomer(data.customer.id, data.customer.name, data.customer.code);
        toast('Customer berhasil ditambahkan!', 'ok');
    } else {
        toast('Gagal menambah customer', 'err');
    }
}

// ============================================================
// CHECKOUT
// ============================================================
async function processCheckout() {
    if (cart.length === 0) return;
    const totalText = document.getElementById('totalDisplay').textContent;
    const total = parseFloat(totalText.replace(/[^0-9]/g,''));
    const paid = selectedPayment === 'cash' ? parseFloat(document.getElementById('paidAmount').value) || 0 : total;
    if (selectedPayment === 'cash' && paid < total) { toast('Nominal bayar kurang!', 'err'); return; }

    const btn = document.getElementById('checkoutBtn');
    btn.disabled = true;
    document.getElementById('checkoutBtnText').innerHTML = '<span class="spinner"></span> Memproses...';

    const discAmt = parseFloat(document.getElementById('discountAmt').value) || 0;
    const discPct = parseFloat(document.getElementById('discountPct').value) || 0;
    const payload = {
        items: cart.map(i => ({ product_id: i.id, quantity: i.qty, price: i.price, discount_amount: i.discount })),
        customer_id: selectedCustomer?.id || null,
        payment_method: selectedPayment,
        paid_amount: paid,
        discount_amount: discAmt,
        discount_percent: discPct,
        pos_class: document.getElementById('posClass').value.trim() || null,
    };

    try {
        const resp = await fetch('/pos/checkout', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            lastReceipt = data.transaction;
            showReceipt(data.transaction);
            toast('Transaksi berhasil: ' + data.invoice_no, 'ok');
        } else {
            toast('Gagal: ' + (data.error || 'Unknown error'), 'err');
        }
    } catch(e) {
        toast('Error koneksi: ' + e.message, 'err');
    } finally {
        btn.disabled = false;
        document.getElementById('checkoutBtnText').innerHTML = '<i class="fas fa-check-circle"></i> Bayar';
    }
}

// ============================================================
// RECEIPT
// ============================================================
function showReceipt(tx) {
    const items = tx.items.map(i =>
        `<div class="receipt-row"><span>${i.product_name} x${i.quantity}</span><span>Rp ${fmt(i.subtotal)}</span></div>`
    ).join('');
    const change = parseFloat(tx.paid_amount) - parseFloat(tx.total);
    document.getElementById('receiptContent').innerHTML = `
        <div class="receipt-header">
            <div class="receipt-title">🧾 HPYSync</div>
            <div style="font-size:11px;margin-top:4px">${new Date().toLocaleString('id-ID')}</div>
        </div>
        <hr class="receipt-divider">
        <div class="receipt-row"><span>No. Invoice</span><span><strong>${tx.invoice_no}</strong></span></div>
        <div class="receipt-row"><span>Kasir</span><span>${'{{ auth()->user()->name }}'}</span></div>
        ${tx.customer ? `<div class="receipt-row"><span>Customer</span><span>${tx.customer.name}</span></div>` : ''}
        <hr class="receipt-divider">
        ${items}
        <hr class="receipt-divider">
        <div class="receipt-row"><span>Subtotal</span><span>Rp ${fmt(tx.subtotal)}</span></div>
        ${parseFloat(tx.discount_amount) > 0 ? `<div class="receipt-row"><span>Diskon</span><span>- Rp ${fmt(tx.discount_amount)}</span></div>` : ''}
        ${parseFloat(tx.tax_amount) > 0 ? `<div class="receipt-row"><span>Pajak</span><span>Rp ${fmt(tx.tax_amount)}</span></div>` : ''}
        <hr class="receipt-divider">
        <div class="receipt-row receipt-total"><span>TOTAL</span><span>Rp ${fmt(tx.total)}</span></div>
        <div class="receipt-row"><span>Bayar (${tx.payment_method.toUpperCase()})</span><span>Rp ${fmt(tx.paid_amount)}</span></div>
        ${tx.payment_method === 'cash' && change > 0 ? `<div class="receipt-row"><span>Kembalian</span><span>Rp ${fmt(change)}</span></div>` : ''}
        <hr class="receipt-divider">
        <div style="text-align:center;font-size:11px;margin-top:8px">Terima kasih atas kunjungan Anda! 🙏</div>
    `;
    document.getElementById('receiptModal').classList.add('show');
}

function printReceipt() {
    if (!lastReceipt) return;
    window.open(`/pos/print/${lastReceipt.id}`, '_blank', 'width=400,height=600');
}

function closeReceiptAndReset() {
    closeModal('receiptModal');
    clearCart();
    clearCustomer({ stopPropagation: () => {} });
    document.getElementById('paidAmount').value = '';
    document.getElementById('discountAmt').value = '';
    document.getElementById('discountPct').value = '';
}

// ============================================================
// UTILS
// ============================================================
function fmt(n) { return parseFloat(n||0).toLocaleString('id-ID',{minimumFractionDigits:0}); }

function toast(msg, type='ok') {
    const c = document.getElementById('toasts');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type==='ok'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
    c.appendChild(t);
    setTimeout(()=>{ t.style.transition='.3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 3000);
}

// Initial render
renderCart();
loadProducts();
</script>
</body>
</html>
