@extends('layouts.fixplay')

@section('title','Kasir Fixplay')
@section('page_title','Kasir Fixplay')

@push('styles')
<style>
  .card-red{
    border-radius:18px; padding:22px; color:#fff;
    background:
      radial-gradient(120% 140% at 0% 0%, rgba(255,255,255,.12), transparent 45%),
      linear-gradient(180deg,#3c24c5,#090a70 40%, #341779);
    box-shadow: 0 18px 48px rgba(220,38,38,.35), 0 0 0 1px rgba(255,255,255,.08) inset;
    min-height:160px; display:flex; align-items:center; justify-content:center; text-align:center;
  }
  .card-red .label{font-weight:800; font-size:22px; letter-spacing:.3px;}
  .card-red .sub{opacity:.9; margin-top:.25rem;}
  .card-red .value{font-size:54px; line-height:1; font-weight:900; margin-top:12px; text-shadow:0 0 14px rgba(255,255,255,.35);}
  .card-graph{ background:#0f1020; border:1px solid rgba(122,92,255,.25); color:#eef2ff; }
  .table-royal thead th{ background:#1f1147; color:#f5f4ff; border-color:#2e1b66!important; }
  .table-royal td, .table-royal th{ border-color:#2e1b66!important; }
</style>
@endpush

@section('page_content')
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card-red">
        <div>
          <div class="label">Ringkasan Hari ini</div>
          <div class="sub">Pendapatan PS (harian)</div>
          <div class="value">Rp {{ number_format($todayPs,0,',','.') }}</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-red">
        <div>
          <div class="label">Ringkasan Hari ini</div>
          <div class="sub">Total Pendapatan (PS + Produk)</div>
          <div class="value">Rp {{ number_format($todayTotal,0,',','.') }}</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-red">
        <div>
          <div class="label">Ringkasan Hari ini</div>
          <div class="sub">Pendapatan Produk (harian)</div>
          <div class="value">Rp {{ number_format($todayProd,0,',','.') }}</div>
        </div>
      </div>
    </div>

    {{-- Grafik pendapatan --}}
    <div class="col-lg-6 mx-auto">
      <div class="card card-graph">
        <div class="card-body">
          <h5 class="mb-3 fw-bold">Grafik pendapatan</h5>
          <canvas id="revChart" height="140"></canvas>
          <div id="chartPayload"
               data-labels='@json($chartLabels)'
               data-series='@json($chartData)'
               hidden></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Transaksi terakhir --}}
  <div class="row g-4 mt-1">
    <div class="col-12">
      <div class="card">
        <div class="card-header py-3" style="background:#1f1147;color:#f5f4ff;font-weight:800;border-bottom:1px solid #2e1b66;">
          Transaksi terakhir
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm m-0 align-middle table-royal">
              <thead>
                <tr>
                  <th style="width: 180px;">Tanggal</th>
                  <th>Transaksi</th>
                  <th class="text-end" style="width: 180px;">Total</th>
                  <th class="text-end d-print-none" style="width: 260px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($lastTx as $t)
                  <tr>
                    <td>{{ $t['date'] }}</td>
                    <td>{{ $t['title'] }}</td>
                    <td class="text-end">Rp {{ number_format($t['total'],0,',','.') }}</td>
                    <td class="text-end d-print-none">
                    {{-- 1. Detail --}}
                    <a href="{{ route('sales.show', $t['id']) }}" class="btn btn-sm btn-outline-secondary text-dark me-1">Detail</a>
                    
                    {{-- 2. Edit (Kuning) --}}
                    <a href="{{ route('sales.edit', $t['id']) }}" class="btn btn-sm btn-outline-warning text-dark me-1">Edit</a>
                    
                    {{-- 3. Hapus (Merah dengan Konfirmasi) --}}
                    <form class="d-inline" method="post" action="{{ route('sales.destroy', $t['id']) }}" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');">
                      @csrf 
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                  </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted p-3">Belum ada transaksi.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  const payload = document.getElementById('chartPayload');
  if(!payload) return;
  const labels = JSON.parse(payload.dataset.labels || '[]');
  const series = JSON.parse(payload.dataset.series || '[]');
  const ctx = document.getElementById('revChart');
  if(!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Pendapatan',
        data: series
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { display: true } }
    }
  });
})();
</script>
@endpush
