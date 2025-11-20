@extends('layouts.fixplay')

@section('page_title','Struk Penjualan')

@section('page_content')
<div class="card card-dark">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="mb-0">Struk Penjualan</h5>
        <div class="text-soft">#{{ $sale->id }} • {{ \Carbon\Carbon::parse($sale->timestamp)->format('d-m-Y H:i') }}</div>
      </div>
      <div class="fw-bold fs-5 amount-mono">Rp {{ number_format($sale->total,0,',','.') }}</div>
    </div>

    <hr class="my-3">

    <div class="table-responsive">
      <table class="table table-sm align-middle m-0 table-neon">
        <thead>
          <tr>
            <th>Item</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Harga</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sale->items as $it)
          <tr>
            <td>{{ $it->product ? $it->product->name : ($it->description ?? '-') }}</td>
            <td class="text-center amount-mono">{{ $it->qty }}</td>
            <td class="text-end amount-mono">Rp {{ number_format($it->unit_price,0,',','.') }}</td>
            <td class="text-end amount-mono">Rp {{ number_format($it->subtotal,0,',','.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if($sale->payment_method)
      <div class="mt-3">
        <span class="text-soft">Metode:</span> <strong>{{ $sale->payment_method }}</strong>
        @if(strtolower($sale->payment_method) === 'tunai')
          • <span class="text-soft">Dibayar:</span> <span class="amount-mono">Rp {{ number_format($sale->paid_amount,0,',','.') }}</span>
          • <span class="text-soft">Kembalian:</span> <span class="amount-mono">Rp {{ number_format($sale->change_amount,0,',','.') }}</span>
        @endif
      </div>
    @endif

    @if(!empty($sale->note))
      <div class="text-soft mt-2">Catatan: {{ $sale->note }}</div>
    @endif

    <div class="mt-3 d-print-none">
      <a href="{{ url('/sessions') }}" class="btn btn-outline-secondary">Kembali</a>
      <button onclick="window.print()" class="btn btn-primary">Cetak</button>
    </div>
  </div>
</div>
@endsection
