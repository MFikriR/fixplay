@extends('layouts.fixplay')

@section('page_title','Kasir Fixplay - Laporan')

@section('page_content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="m-0 text-dark fw-bold">Laporan</h4>
  <button class="btn btn-outline-primary d-print-none" onclick="window.print()">
    <i class="bi bi-printer"></i> Cetak / PDF
  </button>
</div>

<form method="get" class="mb-3 row g-2 align-items-end d-print-none">
  <div class="col-sm-3">
    <label class="form-label fw-bold text-dark">Rentang</label>
    <select name="range" class="form-select" id="rangeSel">
      <option value="day"   @selected($range=='day')>Harian</option>
      <option value="week"  @selected($range=='week')>Mingguan</option>
      <option value="month" @selected($range=='month')>Bulanan</option>
      <option value="custom"@selected($range=='custom')>Kustom (Start–End)</option>
    </select>
  </div>

  <div class="col-sm-3 rng-day rng-week rng-month">
    <label class="form-label fw-bold text-dark">Tanggal referensi</label>
    <input name="date" type="date" class="form-control" value="{{ request('date', $start_date->format('Y-m-d')) }}">
  </div>

  <div class="col-sm-3 rng-custom">
    <label class="form-label fw-bold text-dark">Start</label>
    <input name="start" type="date" class="form-control" value="{{ $start_date->format('Y-m-d') }}">
  </div>
  <div class="col-sm-3 rng-custom">
    <label class="form-label fw-bold text-dark">End</label>
    <input name="end" type="date" class="form-control" value="{{ $end_date->format('Y-m-d') }}">
  </div>

  <div class="col-sm-3">
    <label class="form-label d-block">&nbsp;</label>
    <button class="btn btn-primary w-100">Terapkan</button>
  </div>
</form>

<!-- Ringkasan -->
<div class="row g-3">
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted">Pendapatan PS</div>
      <div class="fs-3 fw-bold">Rp {{ number_format($ps_total,0,',','.') }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted">Pendapatan Produk</div>
      <div class="fs-3 fw-bold">Rp {{ number_format($prod_total,0,',','.') }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted">Total Penjualan</div>
      <div class="fs-3 fw-bold">Rp {{ number_format($sales_total,0,',','.') }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted">Laba Bersih (setelah Pengeluaran)</div>
      <div class="fs-3 fw-bold">Rp {{ number_format($sales_total - $expenses_total,0,',','.') }}</div>
      <div class="small text-muted mt-1">Pengeluaran: Rp {{ number_format($expenses_total,0,',','.') }}</div>
    </div></div>
  </div>
</div>

<!-- Detail transaksi & pengeluaran -->
<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">Penjualan ({{ $start_date->format('d-m-Y') }} — {{ $end_date->format('d-m-Y') }})</div>
      <div class="card-body p-0">
        <table class="table table-sm m-0 align-middle">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Catatan</th>
              <th class="text-end">Total</th>
              <th class="text-end d-print-none"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($sales as $s)
            <tr>
              <td>{{ \Carbon\Carbon::parse($s->sold_at)->format('d-m H:i') }}</td>
              <td>{{ $s->note }}</td>
              <td class="text-end">Rp {{ number_format($s->total,0,',','.') }}</td>
              <td class="text-end d-print-none">
                <a class="btn btn-sm btn-outline-primary bg-white text-dark" href="{{ url('/sales/'.$s->id) }}">Lihat</a>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick='return editSale({{ $s->id }}, {!! json_encode($s->note) !!}, {!! json_encode($s->payment_method) !!}, {{ $s->paid_amount ?? 0 }}, {{ $s->total ?? 0 }})'>
                  Edit
                </button>
                <form class="d-inline confirm-delete" method="POST"
                      action="{{ route('purchases.expenses.destroy', $e->id) }}"
                      onsubmit="return confirm('Hapus pengeluaran ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="fw-bold">
              <td colspan="2">Total PS</td>
              <td class="text-end">Rp {{ number_format($ps_total,0,',','.') }}</td>
              <td class="d-print-none"></td>
            </tr>
            <tr class="fw-bold">
              <td colspan="2">Total Produk</td>
              <td class="text-end">Rp {{ number_format($prod_total,0,',','.') }}</td>
              <td class="d-print-none"></td>
            </tr>
            <tr class="fw-bold">
              <td colspan="2">Total Penjualan</td>
              <td class="text-end">Rp {{ number_format($sales_total,0,',','.') }}</td>
              <td class="d-print-none"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">Pengeluaran ({{ $start_date->format('d-m-Y') }} — {{ $end_date->format('d-m-Y') }})</div>
      <div class="card-body p-0">
        <table class="table table-sm m-0 align-middle">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Kategori</th>
              <th>Deskripsi</th>
              <th class="text-end">Jumlah</th>
              <th class="text-end d-print-none"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($expenses as $e)
            <tr>
              <td>{{ $e->timestamp_fmt ?? (isset($e->timestamp) && $e->timestamp ? $e->timestamp->format('d-m H:i') : '') }}</td>
              <td>{{ $e->category }}</td>
              <td>{{ $e->description }}</td>
              <td class="text-end">Rp {{ number_format($e->amount ?? 0,0,',','.') }}</td>
              <td class="text-end d-print-none">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick='return editExpense({{ $e->id }}, {!! json_encode($e->category) !!}, {!! json_encode($e->description ?? '') !!}, {{ (int)($e->amount ?? 0) }}, {!! json_encode(isset($e->timestamp) ? ($e->timestamp_fmt ?? $e->timestamp) : "") !!})'>
                  Edit
                </button>
                <form class="d-inline" method="POST" action="{{ route('purchases.expenses.destroy', $e->id) }}"
                      onsubmit="return confirm('Hapus pengeluaran ini?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted p-3">Belum ada data.</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr class="fw-bold">
              <td colspan="3">Total Pengeluaran</td>
              <td class="text-end">Rp {{ number_format($expenses_total,0,',','.') }}</td>
              <td class="d-print-none"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Rekap per periode -->
<div class="card mt-3">
  <div class="card-header d-flex align-items-center gap-3">
    <span>Rekap per Periode (dalam rentang terpilih)</span>
    <ul class="nav nav-pills ms-auto d-print-none" id="rekapTabs">
      <li class="nav-item"><button class="nav-link active" data-target="#tabHarian">Harian</button></li>
      <li class="nav-item"><button class="nav-link" data-target="#tabMingguan">Mingguan</button></li>
      <li class="nav-item"><button class="nav-link" data-target="#tabBulanan">Bulanan</button></li>
    </ul>
  </div>
  <div class="card-body p-0">
    <div class="p-3">
      <div id="tabHarian" class="rekap-pane show">
        <table class="table table-sm align-middle">
          <thead><tr><th>Periode</th><th class="text-end">PS</th><th class="text-end">Produk</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            @forelse($daily_rows as $r)
              <tr><td>{{ $r->label }}</td><td class="text-end">Rp {{ number_format($r->ps ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->prod ?? $r->total ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->total ?? 0,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div id="tabMingguan" class="rekap-pane">
        <table class="table table-sm align-middle">
          <thead><tr><th>Periode</th><th class="text-end">PS</th><th class="text-end">Produk</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            @forelse($weekly_rows as $r)
              <tr><td>{{ $r->label }}</td><td class="text-end">Rp {{ number_format($r->ps ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->prod ?? $r->total ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->total ?? 0,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div id="tabBulanan" class="rekap-pane">
        <table class="table table-sm align-middle">
          <thead><tr><th>Periode</th><th class="text-end">PS</th><th class="text-end">Produk</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            @forelse($monthly_rows as $r)
              <tr><td>{{ $r->label }}</td><td class="text-end">Rp {{ number_format($r->ps ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->prod ?? $r->total ?? 0,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->total ?? 0,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Top produk & stok rendah -->
<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Top Produk (Qty) — Rentang terpilih</div>
      <div class="card-body p-0">
        <table class="table table-sm m-0 align-middle">
          <thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Omzet</th></tr></thead>
          <tbody>
            @forelse($top as $t)
              <tr><td>{{ $t->name }}</td><td class="text-center">{{ $t->qty }}</td><td class="text-end">Rp {{ number_format($t->amount,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Stok Rendah (≤ 5)</div>
      <div class="card-body p-0">
        <table class="table table-sm m-0 align-middle">
          <thead><tr><th>Produk</th><th>Stok</th></tr></thead>
          <tbody>
            @forelse($low_stock as $p)
              <tr><td>{{ $p->name }}</td><td>{{ $p->stock }} {{ $p->unit }}</td></tr>
            @empty
              <tr><td colspan="2" class="text-center text-muted">Tidak ada.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .rng-day, .rng-week, .rng-month, .rng-custom { display:none; }
  .rekap-pane { display:none; }
  .rekap-pane.show { display:block; }
  @media print {
    .d-print-none { display: none !important; }
    .card, .table { break-inside: avoid; }
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const sel = document.getElementById('rangeSel');
  function applyRangeUI(){
    const v = sel.value;
    document.querySelectorAll('.rng-day,.rng-week,.rng-month,.rng-custom').forEach(x=>x.style.display='none');
    if (v==='day')   document.querySelectorAll('.rng-day').forEach(x=>x.style.display='block');
    if (v==='week')  document.querySelectorAll('.rng-week').forEach(x=>x.style.display='block');
    if (v==='month') document.querySelectorAll('.rng-month').forEach(x=>x.style.display='block');
    if (v==='custom')document.querySelectorAll('.rng-custom').forEach(x=>x.style.display='block');
  }
  sel && (sel.onchange = applyRangeUI, applyRangeUI());
})();

// Tabs rekap
(function(){
  const tabs = document.querySelectorAll('#rekapTabs .nav-link');
  const panes = document.querySelectorAll('.rekap-pane');
  function show(target){
    panes.forEach(p => p.classList.remove('show'));
    document.querySelector(target)?.classList.add('show');
    tabs.forEach(t => t.classList.remove('active'));
    this.classList.add('active');
  }
  tabs.forEach(t => t.addEventListener('click', function(e){
    e.preventDefault(); show.call(this, this.dataset.target);
  }));
})();

function editSale(id, note, method, paid, total) {
  const newNote = prompt("Catatan:", note || "");
  if (newNote === null) return false;

  const newMethod = prompt("Metode Bayar (Tunai/QRIS/Transfer/Lainnya):", method || "Tunai");
  if (newMethod === null) return false;

  let newPaid = paid;
  if ((newMethod || '').toLowerCase() === 'tunai') {
    const val = prompt("Dibayar (angka):", paid || total);
    if (val === null) return false;
    newPaid = parseInt(val || "0");
  }

  const f = document.createElement('form');
  f.method = 'post';
  f.action = '/sales/' + id + '/update';
  [['note', newNote], ['payment_method', newMethod], ['paid_amount', newPaid]].forEach(([k,v])=>{
    const i = document.createElement('input'); i.type='hidden'; i.name=k; i.value=v; f.appendChild(i);
  });
  document.body.appendChild(f); f.submit();
  return false;
}

function editExpense(id, category, description, amount, ts) {
  const newCat = prompt("Kategori:", category || "");
  if (newCat === null) return false;

  const newDesc = prompt("Deskripsi:", description || "");
  if (newDesc === null) return false;

  const newAmt = prompt("Jumlah (Rp):", amount);
  if (newAmt === null) return false;

  const newTs = prompt("Waktu (YYYY-MM-DDTHH:MM):", ts || "");
  if (newTs === null) return false;

  const f = document.createElement('form');
  f.method = 'post';
  f.action = '/expenses/' + id + '/update';
  [['category', newCat], ['description', newDesc], ['amount', newAmt], ['timestamp', newTs]].forEach(([k,v])=>{
    const i = document.createElement('input'); i.type='hidden'; i.name=k; i.value=v; f.appendChild(i);
  });
  document.body.appendChild(f); f.submit();
  return false;
}
</script>
@endpush
