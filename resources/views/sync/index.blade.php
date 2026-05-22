@extends('layouts.app')
@section('title', 'Sinkronisasi HPY')

@section('content')
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-sync-alt text-blue"></i> Sinkronisasi ERP HPY</div>
        <div class="page-subtitle">Kelola koneksi dan sinkronisasi data ke HPY</div>
    </div>
</div>

{{-- Info bar koneksi aktif --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:8px;">
        @if($settings['erpnext_url'])
            <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;"></span>
            <span style="font-size:13px;font-weight:600;color:var(--text2);">URL:</span>
            <span style="font-size:13px;color:var(--blue);">{{ $settings['erpnext_url'] }}</span>
        @else
            <span style="width:8px;height:8px;border-radius:50%;background:var(--text3);display:inline-block;"></span>
            <span style="font-size:13px;color:var(--text3);">Belum dikonfigurasi</span>
        @endif
    </div>
    @if($settings['erpnext_company'])
    <div style="display:flex;align-items:center;gap:6px;">
        <i class="fas fa-building" style="font-size:12px;color:var(--text3);"></i>
        <span style="font-size:13px;font-weight:600;color:var(--text2);">Company:</span>
        <span style="font-size:13px;color:var(--text);">{{ $settings['erpnext_company'] }}</span>
    </div>
    @endif
    @if($settings['erpnext_pos_profile'])
    <div style="display:flex;align-items:center;gap:6px;">
        <i class="fas fa-cash-register" style="font-size:12px;color:var(--text3);"></i>
        <span style="font-size:13px;font-weight:600;color:var(--text2);">POS Profile:</span>
        <span class="badge badge-blue" style="font-size:12px;">{{ $settings['erpnext_pos_profile'] }}</span>
    </div>
    @else
    <div style="display:flex;align-items:center;gap:6px;">
        <i class="fas fa-cash-register" style="font-size:12px;color:var(--text3);"></i>
        <span style="font-size:13px;color:var(--text3);font-style:italic;">POS Profile belum diset</span>
    </div>
    @endif
</div>

<!-- Stats -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
        <div>
            <div class="stat-value" style="color:#E37400">{{ $stats['pending'] }}</div>
            <div class="stat-label">Menunggu Sync</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-value text-green">{{ $stats['synced'] }}</div>
            <div class="stat-label">Berhasil Disync</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div>
            <div class="stat-value text-red">{{ $stats['failed'] }}</div>
            <div class="stat-label">Gagal Sync</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Connection Settings -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-plug text-blue"></i> Konfigurasi HPY</div>
            <div id="connStatus" class="badge badge-gray"><i class="fas fa-circle" style="font-size:8px"></i> Belum dites</div>
        </div>
        <div class="card-body">
            <form id="settingsForm">
                <div class="form-group">
                    <label class="form-label">HPY URL</label>
                    <input type="url" name="erpnext_url" class="form-control" placeholder="http://your.hpy.co.id" value="{{ $settings['erpnext_url'] ?? '' }}">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="text" name="erpnext_api_key" class="form-control" value="{{ $settings['erpnext_api_key'] ?? '' }}" placeholder="API Key">
                    </div>
                    <div class="form-group">
                        <label class="form-label">API Secret</label>
                        <input type="password" name="erpnext_api_secret" class="form-control" value="{{ $settings['erpnext_api_secret'] ?? '' }}" placeholder="API Secret">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Company</label>
                        <input type="text" name="erpnext_company" class="form-control" value="{{ $settings['erpnext_company'] ?? '' }}" placeholder="Nama perusahaan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">POS Profile</label>
                        <input type="text" name="erpnext_pos_profile" class="form-control" value="{{ $settings['erpnext_pos_profile'] ?? '' }}" placeholder="POS Profile name">
                    </div>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn btn-outline" onclick="testConnection()" id="testBtn">
                        <i class="fas fa-wifi"></i> Test Koneksi
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()" id="saveBtn">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sync Actions -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-exchange-alt" style="color:#34A853"></i> Aksi Sinkronisasi</div>
        </div>
        <div class="card-body">
            <!-- Push Transactions -->
            <div style="background:var(--surface2);border-radius:10px;padding:16px;margin-bottom:12px">
                <div style="font-weight:700;font-size:14px;margin-bottom:4px"><i class="fas fa-arrow-up text-blue"></i> Push ke HPY</div>
                <div style="font-size:13px;color:var(--text3);margin-bottom:12px">Kirim transaksi pending ke HPY sebagai POS Invoice</div>
                <div style="display:flex;gap:8px">
                    <button class="btn btn-primary" onclick="syncAll()" id="syncAllBtn" {{ $stats['pending'] == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-sync-alt"></i> Sync {{ $stats['pending'] }} Pending
                    </button>
                    @if($stats['failed'] > 0)
                    <button class="btn btn-outline" onclick="retryFailed()" id="retryBtn" style="color:#E37400;border-color:#E37400">
                        <i class="fas fa-redo"></i> Retry {{ $stats['failed'] }} Gagal
                    </button>
                    @endif
                </div>
            </div>

            <!-- Pull Data -->
            <div style="background:var(--surface2);border-radius:10px;padding:16px;margin-bottom:12px">
                <div style="font-weight:700;font-size:14px;margin-bottom:4px"><i class="fas fa-arrow-down text-green"></i> Pull dari HPY</div>
                <div style="font-size:13px;color:var(--text3);margin-bottom:12px">Ambil data produk dan customer dari HPY</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="btn btn-success" onclick="pullProducts()" id="pullProductsBtn">
                        <i class="fas fa-box"></i> Pull Produk
                    </button>
                    <button class="btn btn-success" onclick="pullCustomers()" id="pullCustomersBtn">
                        <i class="fas fa-users"></i> Pull Customer
                    </button>
                </div>
            </div>

            <!-- Result box -->
            <div id="syncResult" style="display:none;background:var(--surface2);border-radius:10px;padding:14px;font-size:13px;margin-top:8px"></div>
        </div>
    </div>
</div>

<!-- Failed Transactions -->
@if($failedTransactions->count() > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:#EA4335"></i> Transaksi Gagal Sync</div>
        <button class="btn btn-outline btn-sm" style="color:#E37400;border-color:#E37400" onclick="retryFailed()">
            <i class="fas fa-redo"></i> Retry Semua
        </button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Invoice</th><th>Kasir</th><th>Total</th><th>Error</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            @foreach($failedTransactions as $tx)
            <tr>
                <td class="font-medium text-blue">{{ $tx->invoice_no }}</td>
                <td>{{ $tx->user->name }}</td>
                <td class="money">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                <td class="text-sm" style="color:#EA4335;max-width:300px">
                    <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px" title="{{ $tx->erp_sync_error }}">
                        {{ Str::limit($tx->erp_sync_error, 80) }}
                    </div>
                </td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="syncSingle({{ $tx->id }}, this)">
                        <i class="fas fa-sync-alt"></i> Retry
                    </button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Sync Logs -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list-alt text-blue"></i> Log Sinkronisasi</div>
        <button class="btn btn-ghost btn-sm" onclick="loadLogs()"><i class="fas fa-refresh"></i> Refresh</button>
    </div>
    <div class="table-wrap">
        <table id="logsTable">
            <thead><tr>
                <th>Waktu</th><th>Tipe</th><th>Referensi</th><th>Status</th><th>HPY Doc</th><th>Keterangan</th>
            </tr></thead>
            <tbody>
            @foreach($recentLogs as $log)
            <tr>
                <td class="text-sm text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td><span class="badge badge-blue">{{ strtoupper($log->type) }}</span></td>
                <td class="font-medium">{{ $log->reference_no ?? '#'.$log->reference_id }}</td>
                <td>
                    <span class="badge {{ $log->status === 'success' ? 'badge-green' : ($log->status === 'failed' ? 'badge-red' : 'badge-yellow') }}">
                        {{ strtoupper($log->status) }}
                    </span>
                </td>
                <td class="text-sm">{{ $log->erp_docname ?? '-' }}</td>
                <td class="text-sm" style="color:var(--text3);max-width:250px">
                    <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:230px">
                        {{ $log->error_message ? Str::limit($log->error_message, 60) : '✓ OK' }}
                    </span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function testConnection() {
    const btn = document.getElementById('testBtn');
    const status = document.getElementById('connStatus');
    btn.innerHTML = '<span class="spinner"></span> Mengetes...';
    btn.disabled = true;

    const resp = await fetch('{{ route("sync.test") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    });
    const data = await resp.json();

    if (data.success) {
        status.className = 'badge badge-green';
        status.innerHTML = '<i class="fas fa-circle" style="font-size:8px"></i> Terhubung: ' + data.user;
        toast('Koneksi berhasil! User: ' + data.user, 'success');
    } else {
        status.className = 'badge badge-red';
        status.innerHTML = '<i class="fas fa-circle" style="font-size:8px"></i> Gagal';
        toast('Koneksi gagal: ' + (data.error || 'Error'), 'error');
    }

    btn.innerHTML = '<i class="fas fa-wifi"></i> Test Koneksi';
    btn.disabled = false;
}

async function saveSettings() {
    const btn = document.getElementById('saveBtn');
    const form = document.getElementById('settingsForm');
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);

    btn.innerHTML = '<span class="spinner"></span> Menyimpan...';
    btn.disabled = true;

    const resp = await fetch('{{ route("sync.settings") }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
        body: JSON.stringify(data)
    });
    const result = await resp.json();
    toast(result.success ? 'Pengaturan berhasil disimpan!' : 'Gagal menyimpan', result.success ? 'success' : 'error');
    btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
    btn.disabled = false;
}

async function syncAll() {
    const btn = document.getElementById('syncAllBtn');
    btn.innerHTML = '<span class="spinner"></span> Mensync...';
    btn.disabled = true;

    const resp = await fetch('{{ route("sync.all") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
    });
    const data = await resp.json();
    showSyncResult(data);
    toast(`Sync selesai: ${data.success} berhasil, ${data.failed} gagal`);
    setTimeout(() => location.reload(), 1500);
}

async function retryFailed() {
    const resp = await fetch('{{ route("sync.retry") }}', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
    });
    const data = await resp.json();
    if (data.success) {
        toast(`${data.reset} transaksi direset ke pending`, 'success');
        setTimeout(() => location.reload(), 1000);
    }
}

async function syncSingle(id, btn) {
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;
    const resp = await fetch(`/sync/transaction/${id}`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
    });
    const data = await resp.json();
    toast(data.success ? 'Berhasil disync: ' + data.docname : 'Gagal: ' + data.error, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 1000);
    else { btn.innerHTML = '<i class="fas fa-sync-alt"></i> Retry'; btn.disabled = false; }
}

async function pullProducts() {
    const btn = document.getElementById('pullProductsBtn');
    btn.innerHTML = '<span class="spinner"></span> Menarik semua produk...';
    btn.disabled = true;

    // Tampilkan info bahwa ini mungkin butuh waktu untuk data besar
    const resultEl = document.getElementById('syncResult');
    resultEl.style.display = '';
    resultEl.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--blue)"></i> Menarik produk dari HPY, harap tunggu... (data besar mungkin butuh beberapa menit)';

    try {
        const resp = await fetch('{{ route("sync.pull-products") }}', {
            method:'POST',
            headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        });
        const data = await resp.json();
        if (data.success) {
            showSyncResult({...data, label: 'Produk'});
            toast(`Produk: ${data.imported} baru, ${data.updated} diupdate — total ${data.total} record`, 'success');
        } else {
            resultEl.innerHTML = `<i class="fas fa-exclamation-circle" style="color:var(--red)"></i> Gagal: ${data.error}`;
            toast('Gagal pull produk: ' + (data.error||'Error'), 'error');
        }
    } catch(e) {
        resultEl.innerHTML = `<i class="fas fa-exclamation-circle" style="color:var(--red)"></i> Error: ${e.message}`;
        toast('Error: ' + e.message, 'error');
    }

    btn.innerHTML = '<i class="fas fa-box"></i> Pull Produk';
    btn.disabled = false;
}

async function pullCustomers() {
    const btn = document.getElementById('pullCustomersBtn');
    btn.innerHTML = '<span class="spinner"></span> Menarik semua customer...';
    btn.disabled = true;

    const resultEl = document.getElementById('syncResult');
    resultEl.style.display = '';
    resultEl.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--blue)"></i> Menarik customer dari HPY, harap tunggu... (data besar mungkin butuh beberapa menit)';

    try {
        const resp = await fetch('{{ route("sync.pull-customers") }}', {
            method:'POST',
            headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        });
        const data = await resp.json();
        if (data.success) {
            showSyncResult({...data, label: 'Customer'});
            toast(`Customer: ${data.imported} baru, ${data.updated} diupdate — total ${data.total} record`, 'success');
        } else {
            resultEl.innerHTML = `<i class="fas fa-exclamation-circle" style="color:var(--red)"></i> Gagal: ${data.error}`;
            toast('Gagal pull customer: ' + (data.error||'Error'), 'error');
        }
    } catch(e) {
        resultEl.innerHTML = `<i class="fas fa-exclamation-circle" style="color:var(--red)"></i> Error: ${e.message}`;
        toast('Error: ' + e.message, 'error');
    }

    btn.innerHTML = '<i class="fas fa-users"></i> Pull Customer';
    btn.disabled = false;
}

function showSyncResult(data) {
    const el = document.getElementById('syncResult');
    el.style.display = 'block';
    if (data.label) {
        el.innerHTML = `<i class="fas fa-check-circle" style="color:#34A853"></i> <strong>${data.label}:</strong> `
            + `<span style="color:var(--green);font-weight:600;">${data.imported ?? 0} baru</span>, `
            + `<span style="color:var(--blue);font-weight:600;">${data.updated ?? 0} diupdate</span> `
            + `— total <strong>${data.total ?? 0}</strong> record diproses`;
    } else {
        el.innerHTML = `<i class="fas fa-sync-alt" style="color:#4285F4"></i> <strong>Sync selesai:</strong> ${data.success} berhasil, ${data.failed} gagal dari ${data.total} transaksi`;
    }
}
</script>
@endpush
