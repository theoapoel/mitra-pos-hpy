@extends('layouts.app')
@section('title', 'Pengaturan Toko')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-store text-blue" style="margin-right:8px;"></i>Pengaturan Toko
        </h1>
        <p class="page-subtitle">Informasi toko yang tampil di struk pembayaran</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    {{-- Form Pengaturan --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-edit text-blue" style="margin-right:6px;"></i>Informasi Toko</span>
            <span id="saveStatus" class="badge badge-gray" style="display:none;"></span>
        </div>
        <div class="card-body">
            <form id="storeSettingsForm">
                <div class="form-group">
                    <label class="form-label">Nama Toko <span style="color:var(--red)">*</span></label>
                    <input type="text" name="store_name" class="form-control"
                        value="{{ $settings['store_name'] }}"
                        placeholder="Contoh: HAPPYPOS" maxlength="100"
                        oninput="updatePreview()">
                    @error('store_name')<p style="font-size:12px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tagline / Slogan</label>
                    <input type="text" name="store_tagline" class="form-control"
                        value="{{ $settings['store_tagline'] }}"
                        placeholder="Contoh: Point of Sale System" maxlength="150"
                        oninput="updatePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Toko</label>
                    <textarea name="store_address" class="form-control" rows="2"
                        placeholder="Jl. Contoh No. 1, Jakarta" maxlength="300"
                        oninput="updatePreview()">{{ $settings['store_address'] }}</textarea>
                </div>

                <div class="grid-2 gap-3">
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="store_phone" class="form-control"
                            value="{{ $settings['store_phone'] }}"
                            placeholder="021-12345678" maxlength="30"
                            oninput="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="store_email" class="form-control"
                            value="{{ $settings['store_email'] }}"
                            placeholder="info@toko.com" maxlength="100"
                            oninput="updatePreview()">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Pesan Footer Struk</label>
                    <input type="text" name="receipt_footer" class="form-control"
                        value="{{ $settings['receipt_footer'] }}"
                        placeholder="Terima kasih atas kunjungan Anda!" maxlength="200"
                        oninput="updatePreview()">
                    <p style="font-size:12px;color:var(--text3);margin-top:4px;">Pesan yang tampil di bagian bawah struk</p>
                </div>

                <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveSettings()">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

    {{-- Preview Struk --}}
    <div>
        <div class="card" style="position:sticky;top:80px;">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-receipt text-blue" style="margin-right:6px;"></i>Preview Struk</span>
                <span style="font-size:12px;color:var(--text3);">Tampilan thermal printer</span>
            </div>
            <div class="card-body" style="display:flex;justify-content:center;">
                <div id="receiptPreview" style="font-family:'Courier New',monospace;font-size:12px;width:260px;border:1px dashed var(--border);padding:14px;background:#fff;border-radius:6px;line-height:1.6;">
                    {{-- diisi oleh JS --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function val(name) {
    return document.querySelector(`[name="${name}"]`)?.value?.trim() ?? '';
}

function updatePreview() {
    const name    = val('store_name')     || 'HAPPYPOS';
    const tagline = val('store_tagline')  || 'Point of Sale System';
    const address = val('store_address');
    const phone   = val('store_phone');
    const email   = val('store_email');
    const footer  = val('receipt_footer') || 'Terima kasih atas kunjungan Anda!';

    const divider = `<div style="border-top:1px dashed #000;margin:5px 0;"></div>`;

    let html = `<div style="text-align:center;font-weight:bold;font-size:15px;">${name}</div>`;
    if (tagline) html += `<div style="text-align:center;font-size:10px;">${tagline}</div>`;
    if (address) html += `<div style="text-align:center;font-size:10px;">${address.replace(/\n/g,'<br>')}</div>`;
    if (phone)   html += `<div style="text-align:center;font-size:10px;">Telp: ${phone}</div>`;
    if (email)   html += `<div style="text-align:center;font-size:10px;">${email}</div>`;

    html += divider;
    html += `<div style="display:flex;justify-content:space-between;"><span>Invoice</span><span>INV-YYYYMMDD-0001</span></div>`;
    html += `<div style="display:flex;justify-content:space-between;"><span>Tanggal</span><span>${new Date().toLocaleDateString('id-ID')}</span></div>`;
    html += `<div style="display:flex;justify-content:space-between;"><span>Kasir</span><span>Admin</span></div>`;
    html += divider;
    html += `<div>Produk Contoh</div><div style="display:flex;justify-content:space-between;padding-left:8px;"><span>1 x Rp 50.000</span><span>Rp 50.000</span></div>`;
    html += divider;
    html += `<div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span>Rp 50.000</span></div>`;
    html += divider;
    html += `<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:14px;"><span>TOTAL</span><span>Rp 50.000</span></div>`;
    html += `<div style="display:flex;justify-content:space-between;"><span>Bayar (CASH)</span><span>Rp 100.000</span></div>`;
    html += `<div style="display:flex;justify-content:space-between;"><span>Kembalian</span><span>Rp 50.000</span></div>`;
    html += divider;
    html += `<div style="text-align:center;margin-top:6px;">${footer}</div>`;

    document.getElementById('receiptPreview').innerHTML = html;
}

async function saveSettings() {
    const btn    = document.getElementById('saveBtn');
    const status = document.getElementById('saveStatus');
    const form   = document.getElementById('storeSettingsForm');
    const data   = {};

    new FormData(form).forEach((v, k) => data[k] = v);

    btn.innerHTML = '<span class="spinner"></span> Menyimpan...';
    btn.disabled  = true;
    status.style.display = 'none';

    try {
        const res = await api.post('{{ route("settings.save") }}', data);

        if (res.success) {
            status.className     = 'badge badge-green';
            status.textContent   = '✓ Tersimpan';
            status.style.display = '';
            toast('Pengaturan berhasil disimpan!', 'success');
            setTimeout(() => status.style.display = 'none', 3000);
        } else {
            toast('Gagal menyimpan: ' + (res.message || 'Error'), 'error');
        }
    } catch(e) {
        toast('Error: ' + e.message, 'error');
    }

    btn.innerHTML = '<i class="fas fa-save"></i> Simpan Pengaturan';
    btn.disabled  = false;
}

// Init preview on load
updatePreview();
</script>
@endpush
