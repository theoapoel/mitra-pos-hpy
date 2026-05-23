<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir — HPYSync</title>
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
        .categories-wrap { position: relative; background: var(--surface); border-bottom: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; }
        .categories-bar { display: flex; gap: 8px; padding: 10px 8px; overflow-x: auto; flex: 1; scroll-behavior: smooth; }
        .categories-bar::-webkit-scrollbar { display: none; }
        .cat-arrow { flex-shrink: 0; width: 32px; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: none; cursor: pointer; color: var(--text2); font-size: 14px; transition: all .2s; z-index: 2; padding: 0 4px; }
        .cat-arrow:hover { color: var(--blue); background: var(--blue-light); }
        .cat-arrow.hidden { opacity: 0; pointer-events: none; }
        .cat-arrow-left  { border-right: 1px solid var(--border); }
        .cat-arrow-right { border-left:  1px solid var(--border); }
        .cat-btn { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; white-space: nowrap; transition: all .2s; font-family:'Google Sans',sans-serif; }
        .cat-btn.all { background: var(--blue); color: #fff; }
        .cat-btn:not(.all) { background: var(--surface2); color: var(--text2); border: 1px solid var(--border); }
        .cat-btn:not(.all):hover { background: var(--blue-light); color: var(--blue); border-color: var(--blue); }
        .cat-btn.active { color: #fff; border-color: transparent; }

        /* Products Grid */
        .products-grid { flex:1; overflow-y:auto; padding:10px; display:grid; grid-template-columns:repeat(2,1fr); gap:8px; align-content:start; }
        .products-grid.text-mode { grid-template-columns:repeat(2,1fr); gap:6px; padding:8px; background:var(--bg); align-content:start; }
        /* IMAGE MODE card — horizontal */
        .product-card {
            background:var(--surface); border-radius:10px; border:2px solid var(--border);
            cursor:pointer; transition:all .2s; overflow:hidden; position:relative;
            display:flex; flex-direction:row; align-items:stretch; min-height:88px;
        }
        .product-card:hover { box-shadow:var(--shadow); border-color:var(--blue); transform:translateY(-1px); }
        .product-card.out-of-stock { opacity:.5; cursor:not-allowed; }
        .product-card.out-of-stock:hover { transform:none; box-shadow:none; border-color:var(--border); }
        .product-img {
            width:88px; min-width:88px; min-height:88px; flex-shrink:0;
            background:var(--surface2); position:relative; overflow:hidden;
            display:flex; align-items:center; justify-content:center; font-size:30px;
        }
        .product-img img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
        .product-cat-dot { position:absolute; bottom:5px; right:5px; width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .product-info { padding:10px 12px; flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center; gap:3px; }
        .product-name { font-size:13px; font-weight:600; line-height:1.4; color:var(--text); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .product-price { font-family:'Google Sans',sans-serif; font-size:14px; font-weight:700; color:var(--blue); }
        .product-stock { font-size:11px; color:var(--text3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .stock-low { color:#E37400; font-weight:600; }
        .low-stock-badge { position:absolute; top:5px; left:5px; background:#FBBC05; color:#202124; font-size:9px; font-weight:700; padding:2px 5px; border-radius:6px; }
        /* TEXT MODE card — compact card */
        .product-card.text-card {
            min-height:unset; flex-direction:row; align-items:center;
            padding:14px 12px; gap:10px; border-radius:10px;
        }

        /* RIGHT: Cart Panel */
        .pos-cart { width: 520px; background: var(--surface); border-left: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }

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
    <img src="{{ asset('images/happypos.png') }}" alt="HPYSync"
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
        <div class="categories-wrap">
            <button class="cat-arrow cat-arrow-left hidden" id="catArrowLeft" onclick="scrollCats(-1)" title="Geser kiri">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="categories-bar" id="categoriesBar">
                <button class="cat-btn all active" onclick="filterCategory(null, this)">Semua</button>
                @foreach($categories as $cat)
                <button class="cat-btn" style="--cat-color:{{ $cat->color }}"
                    onclick="filterCategory({{ $cat->id }}, this)"
                    data-id="{{ $cat->id }}">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
            <button class="cat-arrow cat-arrow-right hidden" id="catArrowRight" onclick="scrollCats(1)" title="Geser kanan">
                <i class="fas fa-chevron-right"></i>
            </button>
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
                <i class="fas fa-user-circle" id="customerIcon"></i>
                <span id="customerBtnText">Memuat...</span>
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
                    <input type="number" id="discountAmt" class="discount-input" placeholder="0" min="0" oninput="syncTxDiscount('amt')">
                </div>
                <div style="flex:1">
                    <div style="font-size:11px;color:var(--text3);margin-bottom:3px">Diskon (%)</div>
                    <input type="number" id="discountPct" class="discount-input" placeholder="0" min="0" max="100" oninput="syncTxDiscount('pct')">
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

<!-- Item Discount Modal -->
<div class="modal-overlay" id="itemDiscountModal">
    <div class="modal" style="max-width:360px">
        <div class="modal-header">
            <div class="modal-title" style="font-size:15px"><i class="fas fa-tag" style="color:var(--red);margin-right:6px"></i>Diskon Item</div>
            <button onclick="closeModal('itemDiscountModal')" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text3)">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px 24px">
            <div style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:16px" id="discItemName"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px">Diskon (Rp)</label>
                    <input type="number" id="discItemAmt" min="0" placeholder="0"
                        oninput="syncDiscModal('amt')"
                        style="width:100%;padding:10px 12px;border:2px solid var(--border);border-radius:8px;font-size:15px;font-weight:700;font-family:'Roboto Mono',monospace">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px">Diskon (%)</label>
                    <input type="number" id="discItemPct" min="0" max="100" placeholder="0"
                        oninput="syncDiscModal('pct')"
                        style="width:100%;padding:10px 12px;border:2px solid var(--border);border-radius:8px;font-size:15px;font-weight:700;font-family:'Roboto Mono',monospace">
                </div>
            </div>
            <div id="discItemPreview" style="margin-top:14px;padding:10px 14px;background:var(--surface2);border-radius:8px;font-size:13px;display:none">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="color:var(--text3)">Harga × Qty</span>
                    <span id="discItemGross" style="font-weight:600"></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="color:var(--red)">Diskon</span>
                    <span id="discItemDiscVal" style="color:var(--red);font-weight:600"></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px dashed var(--border);padding-top:6px">
                    <span style="font-weight:700">Subtotal</span>
                    <span id="discItemNet" style="font-weight:700;color:var(--green)"></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="clearItemDiscount()"><i class="fas fa-times"></i> Hapus Diskon</button>
            <button class="btn btn-primary" onclick="applyItemDiscount()"><i class="fas fa-check"></i> Terapkan</button>
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
const defaultPosClass = @json($posClass);
const walkinCustomerName = @json($walkinCustomerName);
const posProductDisplay = @json($posProductDisplay);
const erpBaseUrl = @json($erpBaseUrl);
const appBaseUrl = @json(url('/'));

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
    const url = '{{ route("pos.search-products") }}' + '?q=' + (categoryId ? '&category_id=' + categoryId : '');
    try {
        const resp = await fetch(url, { headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf} });
        const products = await resp.json();
        allProducts = Array.isArray(products) ? products : [];
        renderProducts(allProducts);
    } catch(e) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--red)"><div style="font-size:32px;margin-bottom:8px">⚠️</div><div>Gagal memuat produk</div></div>';
    }
}

function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    if (products.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">📦</div><div>Tidak ada produk</div></div>';
        return;
    }
    const outOfStock = p => p.track_stock && p.stock <= 0;
    const stockLabel = p => p.track_stock ? (p.stock <= 0 ? '⚠ Habis' : 'Stok: '+p.stock+' '+p.unit) : '∞';
    products = [...products].sort((a, b) => {
        const aOut = outOfStock(a) ? 1 : 0;
        const bOut = outOfStock(b) ? 1 : 0;
        return aOut - bOut;
    });
    const imgUrl     = p => {
        if (!p.image) return null;
        if (p.image.startsWith('http')) return p.image;
        if (p.image.startsWith('/images/')) return appBaseUrl + p.image;
        return erpBaseUrl + p.image; // fallback: path relatif ERPNext (/files/...)
    };
    const cardAttrs  = (p, extra = '') => `
        class="product-card ${extra} ${outOfStock(p) ? 'out-of-stock' : ''}"
        data-id="${p.id}" data-name="${p.name.replace(/'/g,"&#39;")}"
        data-price="${p.price}" data-sku="${p.sku}" data-stock="${p.stock}"
        data-unit="${p.unit}" data-tax="${p.tax_rate}"
        data-track="${p.track_stock ? 1 : 0}" data-category="${p.category_id || ''}"
        onclick="addToCart(this)"`;

    if (posProductDisplay === 'text') {
        grid.classList.add('text-mode');
        grid.innerHTML = products.map(p => {
            const stockInfo = p.track_stock
                ? (p.stock <= 0
                    ? `<span style="margin-left:6px;font-size:10px;font-weight:700;background:#FCE8E6;color:var(--red);padding:1px 5px;border-radius:8px">Habis</span>`
                    : (p.is_low_stock ? `<span style="margin-left:6px;font-size:10px;font-weight:700;background:#FEF3C7;color:#92400E;padding:1px 5px;border-radius:8px">Stok: ${p.stock}</span>` : ''))
                : '';
            return `
            <div ${cardAttrs(p, 'text-card')}>
                <span style="width:8px;min-width:8px;height:8px;border-radius:50%;background:${p.category_color||'#4285F4'}"></span>
                <div style="flex:1;min-width:0;display:flex;align-items:center;gap:0;overflow:hidden">
                    <span style="font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:${outOfStock(p)?'var(--text3)':'var(--text)'}">${p.name}</span>
                    ${stockInfo}
                </div>
                <div style="font-family:'Google Sans',sans-serif;font-size:14px;font-weight:700;color:${outOfStock(p)?'var(--text3)':'var(--blue)'};white-space:nowrap;flex-shrink:0">Rp ${fmt(p.price)}</div>
            </div>`
        }).join('');
    } else {
        grid.classList.remove('text-mode');
        const src = p => imgUrl(p);
        grid.innerHTML = products.map(p => `
            <div ${cardAttrs(p)}>
                <div class="product-img">
                    ${src(p)
                        ? `<img src="${src(p)}" alt="" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display=''">`
                        : ''}
                    <span style="font-size:28px;${src(p)?'display:none':''}">📦</span>
                    <span class="product-cat-dot" style="background:${p.category_color||'#4285F4'}"></span>
                    ${p.is_low_stock ? '<span class="low-stock-badge">LOW</span>' : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${p.name}</div>
                    <div class="product-price">Rp ${fmt(p.price)}</div>
                    <div class="product-stock ${p.is_low_stock?'stock-low':''}">
                        ${p.track_stock ? (p.stock<=0 ? '⚠ Habis' : 'Stok: '+p.stock+' '+p.unit) : '∞ Tdk terbatas'}
                    </div>
                </div>
            </div>
        `).join('');
    }
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
        const url = '{{ route("pos.search-products") }}' + '?q=' + encodeURIComponent(term) + (currentCategoryFilter ? '&category_id=' + currentCategoryFilter : '');
        try {
            const resp = await fetch(url, { headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf} });
            const products = await resp.json();
            allProducts = Array.isArray(products) ? products : [];
            renderProducts(allProducts);
        } catch(e) {
            console.error('Search error:', e);
        }
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

    if (track && stock <= 0) toast('⚠ Stok habis — ditambahkan tetapi perlu konfirmasi', 'err');

    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id, name: el.dataset.name, price: parseFloat(el.dataset.price),
            sku: el.dataset.sku, stock, unit: el.dataset.unit,
            tax: parseFloat(el.dataset.tax), track,
            qty: 1, discount: 0, discountPct: 0
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

function setPrice(id, val) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const price = parseFloat(val) || 0;
    if (price < 0) return;
    item.price = price;
    renderCart();
}

// ============================================================
// ITEM DISCOUNT
// ============================================================
let discItemId = null;

function openItemDiscount(id) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    discItemId = id;

    document.getElementById('discItemName').textContent = item.name;
    document.getElementById('discItemAmt').value = item.discount > 0 ? item.discount : '';
    document.getElementById('discItemPct').value = item.discountPct > 0 ? item.discountPct : '';
    updateDiscPreview(item);
    document.getElementById('itemDiscountModal').classList.add('show');
    setTimeout(() => document.getElementById('discItemAmt').focus(), 100);
}

function syncDiscModal(mode) {
    const item = cart.find(i => i.id === discItemId);
    if (!item) return;
    const gross = item.price * item.qty;

    if (mode === 'amt') {
        const amt = parseFloat(document.getElementById('discItemAmt').value) || 0;
        document.getElementById('discItemPct').value = gross > 0 ? parseFloat(((amt / gross) * 100).toFixed(2)) : '';
    } else {
        const pct = parseFloat(document.getElementById('discItemPct').value) || 0;
        document.getElementById('discItemAmt').value = parseFloat(((pct / 100) * gross).toFixed(0));
    }
    updateDiscPreview(item);
}

function updateDiscPreview(item) {
    const gross   = item.price * item.qty;
    const amt     = parseFloat(document.getElementById('discItemAmt').value) || 0;
    const net     = Math.max(0, gross - amt);
    const preview = document.getElementById('discItemPreview');

    if (amt > 0) {
        preview.style.display = 'block';
        document.getElementById('discItemGross').textContent   = 'Rp ' + fmt(gross);
        document.getElementById('discItemDiscVal').textContent = '- Rp ' + fmt(amt);
        document.getElementById('discItemNet').textContent     = 'Rp ' + fmt(net);
    } else {
        preview.style.display = 'none';
    }
}

function applyItemDiscount() {
    const item = cart.find(i => i.id === discItemId);
    if (!item) return;
    const gross = item.price * item.qty;
    const amt   = parseFloat(document.getElementById('discItemAmt').value) || 0;
    const pct   = parseFloat(document.getElementById('discItemPct').value) || 0;

    if (amt > gross) { toast('Diskon melebihi harga item!', 'err'); return; }

    item.discount    = amt;
    item.discountPct = pct;
    closeModal('itemDiscountModal');
    renderCart();
    if (amt > 0) toast('Diskon item diterapkan', 'ok');
}

function clearItemDiscount() {
    const item = cart.find(i => i.id === discItemId);
    if (item) { item.discount = 0; item.discountPct = 0; }
    closeModal('itemDiscountModal');
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    cart = [];
    document.getElementById('discountAmt').value = '';
    document.getElementById('discountPct').value = '';
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
        const gross    = item.price * item.qty;
        const subtotal = Math.max(0, gross - (item.discount || 0));
        const hasDisc  = item.discount > 0;
        html += `
        <div class="cart-item">
            <div class="cart-item-info">
                <div style="display:flex;align-items:center;gap:6px">
                    <div class="cart-item-name" style="flex:1">${item.name}</div>
                    ${hasDisc ? `<span style="font-size:10px;font-weight:700;background:#FCE8E6;color:var(--red);padding:2px 6px;border-radius:10px;white-space:nowrap">-Rp ${fmt(item.discount)}</span>` : ''}
                </div>
                <div class="cart-item-controls" style="margin-top:6px;flex-wrap:wrap;gap:4px">
                    <button class="qty-btn" onclick="updateQty(${item.id},-1)">−</button>
                    <input class="qty-input" type="number" value="${item.qty}" min="1"
                        onchange="setQty(${item.id},this.value)" style="width:36px">
                    <button class="qty-btn" onclick="updateQty(${item.id},1)">+</button>
                    <span style="font-size:11px;color:var(--text3);margin:0 2px">×</span>
                    <div style="position:relative;display:inline-flex;align-items:center">
                        <span style="position:absolute;left:9px;font-size:12px;color:var(--text3);pointer-events:none">Rp</span>
                        <input type="number" min="0" value="${item.price}"
                            onchange="setPrice(${item.id},this.value)"
                            onclick="this.select()"
                            style="width:130px;padding:5px 8px 5px 30px;border:1px solid var(--blue);border-radius:8px;font-size:15px;font-weight:700;font-family:'Roboto Mono',monospace;color:var(--blue)">
                    </div>
                    <button onclick="openItemDiscount(${item.id})"
                        title="Diskon item"
                        style="padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid ${hasDisc ? 'var(--red)' : 'var(--border)'};background:${hasDisc ? '#FCE8E6' : 'var(--surface2)'};color:${hasDisc ? 'var(--red)' : 'var(--text2)'};cursor:pointer;white-space:nowrap">
                        <i class="fas fa-tag"></i> Diskon
                    </button>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                <i class="fas fa-times cart-item-remove" onclick="removeFromCart(${item.id})"></i>
                ${hasDisc ? `<span style="font-size:11px;color:var(--text3);text-decoration:line-through">Rp ${fmt(gross)}</span>` : ''}
                <span class="cart-item-subtotal" style="${hasDisc ? 'color:var(--red)' : ''}">Rp ${fmt(subtotal)}</span>
            </div>
        </div>`;
    });

    container.querySelectorAll('.cart-item').forEach(el => el.remove());
    empty.insertAdjacentHTML('afterend', html);
    recalculate();
    document.getElementById('checkoutBtn').disabled = false;
}

function cartNetSubtotal() {
    return cart.reduce((s, i) => s + Math.max(0, (i.price * i.qty) - (i.discount || 0)), 0);
}

function syncTxDiscount(mode) {
    const subtotal = cartNetSubtotal();
    if (mode === 'pct') {
        const pct = parseFloat(document.getElementById('discountPct').value) || 0;
        const amt = subtotal * (pct / 100);
        document.getElementById('discountAmt').value = amt > 0 ? Math.round(amt) : '';
    } else {
        const amt = parseFloat(document.getElementById('discountAmt').value) || 0;
        const pct = subtotal > 0 ? (amt / subtotal) * 100 : 0;
        document.getElementById('discountPct').value = pct > 0 ? parseFloat(pct.toFixed(2)) : '';
    }
    recalculate();
}

function recalculate() {
    let subtotal = cartNetSubtotal();
    let tax = cart.reduce((s, i) => s + (Math.max(0, (i.price * i.qty) - (i.discount || 0)) * (i.tax / 100)), 0);
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
const allCustomers = @json($customers);

function renderCustomerBtn() {
    const btn     = document.getElementById('customerBtn');
    const textEl  = document.getElementById('customerBtnText');
    const clearEl = document.getElementById('customerClearBtn');

    if (!selectedCustomer || selectedCustomer.id === null) {
        btn.classList.remove('has-customer');
        btn.style.borderStyle = 'solid';
        btn.style.borderColor = 'var(--border)';
        btn.style.background  = 'var(--surface2)';
        btn.style.color       = 'var(--text2)';
        textEl.textContent    = '🚶 ' + walkinCustomerName;
        clearEl.style.display = 'none';
    } else {
        btn.classList.add('has-customer');
        btn.style.borderStyle = '';
        btn.style.borderColor = '';
        btn.style.background  = '';
        btn.style.color       = '';
        textEl.textContent    = '👤 ' + selectedCustomer.name + ' (' + selectedCustomer.code + ')';
        clearEl.style.display = 'block';
    }
}

function setWalkin() {
    selectedCustomer = { id: null, name: walkinCustomerName };
    renderCustomerBtn();
}

function openCustomerModal() {
    document.getElementById('customerModal').classList.add('show');
    document.getElementById('customerSearch').value = '';
    renderCustomerList(allCustomers);
    setTimeout(() => document.getElementById('customerSearch').focus(), 100);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function renderCustomerList(list) {
    const el = document.getElementById('customerList');

    // Walk-in row selalu tampil di atas
    const walkinRow = `
        <div onclick="selectWalkin()"
            style="padding:10px 12px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:10px;background:${(!selectedCustomer || selectedCustomer.id === null) ? '#E8F0FE' : ''};transition:background .15s"
            onmouseover="this.style.background='#E8F0FE'" onmouseout="this.style.background='${(!selectedCustomer || selectedCustomer.id === null) ? '#E8F0FE' : ''}'">
            <span style="font-size:18px">🚶</span>
            <div>
                <div style="font-weight:700;font-size:14px;color:#4285F4">${walkinCustomerName}</div>
                <div style="font-size:12px;color:var(--text3)">Transaksi tanpa pelanggan terdaftar</div>
            </div>
            ${(!selectedCustomer || selectedCustomer.id === null) ? '<i class="fas fa-check" style="margin-left:auto;color:#4285F4"></i>' : ''}
        </div>`;

    if (list.length === 0) {
        el.innerHTML = walkinRow + '<div style="padding:20px;text-align:center;color:var(--text3);font-size:14px">Tidak ditemukan</div>';
        return;
    }
    el.innerHTML = walkinRow + list.slice(0, 8).map(c => `
        <div onclick="selectCustomer(${c.id},'${c.name.replace(/'/g,"\\'")}','${c.code}')"
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

function selectWalkin() {
    setWalkin();
    closeModal('customerModal');
}

function selectCustomer(id, name, code) {
    selectedCustomer = { id, name, code };
    renderCustomerBtn();
    closeModal('customerModal');
}

function clearCustomer(e) {
    if (e) e.stopPropagation();
    setWalkin();
}

async function addNewCustomer() {
    const name = document.getElementById('newCustName').value.trim();
    if (!name) { toast('Nama customer wajib diisi!', 'err'); return; }
    const phone = document.getElementById('newCustPhone').value.trim();
    const resp = await fetch('{{ route("customers.store") }}', {
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
    if (!selectedCustomer) { toast('Pilih customer terlebih dahulu!', 'err'); openCustomerModal(); return; }
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
        pos_class: defaultPosClass || null,
    };

    try {
        const resp = await fetch('{{ route("pos.checkout") }}', {
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
            <div class="receipt-title">HPYSync</div>
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
    window.open(`{{ url('pos/print') }}/${lastReceipt.id}`, '_blank', 'width=400,height=600');
}

function closeReceiptAndReset() {
    closeModal('receiptModal');
    clearCart();
    setWalkin();
    document.getElementById('paidAmount').value = '';
    document.getElementById('discountAmt').value = '';
    document.getElementById('discountPct').value = '';
}

// ============================================================
// CATEGORIES SCROLL ARROWS
// ============================================================
const catsBar = document.getElementById('categoriesBar');

function updateCatArrows() {
    const atStart = catsBar.scrollLeft <= 4;
    const atEnd   = catsBar.scrollLeft + catsBar.clientWidth >= catsBar.scrollWidth - 4;
    document.getElementById('catArrowLeft').classList.toggle('hidden', atStart);
    document.getElementById('catArrowRight').classList.toggle('hidden', atEnd);
}

function scrollCats(dir) {
    catsBar.scrollBy({ left: dir * 200, behavior: 'smooth' });
}

catsBar.addEventListener('scroll', updateCatArrows);
// Cek setelah render selesai
setTimeout(updateCatArrows, 100);

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
setWalkin();
renderCart();
loadProducts();
</script>
</body>
</html>
