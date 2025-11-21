@extends('layouts.fixplay')

@section('page_title', 'Edit Transaksi')

@section('page_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-dark fw-bold">Edit Transaksi #{{ $sale->id }}</h4>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
    </div>

    <div class="card card-dark">
        <div class="card-body">
            <form action="{{ route('sales.update', $sale->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="alert alert-info">
                    <strong>Info:</strong> Mengubah <b>Total Transaksi</b> akan memperbarui harga item/sesi di database agar laporan akurat.
                </div>

                <div class="row mb-3">
                    {{-- KOLOM TOTAL TRANSAKSI (DIBUAT BISA DIEDIT) --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Total Transaksi (Tagihan)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            {{-- Menggunakan nama 'total_bill' agar dibaca controller --}}
                            <input type="number" name="total_bill" class="form-control fw-bold text-primary" 
                                   value="{{ $sale->total_amount > 0 ? $sale->total_amount : $sale->items->sum('subtotal') }}" required>
                        </div>
                        <small class="text-muted">Ubah angka ini jika harga sesi/item salah.</small>
                    </div>

                    {{-- KOLOM WAKTU TRANSAKSI (DIBUAT BISA DIEDIT) --}}
                    <div class="col-md-6">
                        <label class="form-label">Waktu Transaksi</label>
                        {{-- Menggunakan datetime-local --}}
                        <input type="datetime-local" name="created_at" class="form-control" 
                               value="{{ \Carbon\Carbon::parse($sale->sold_at ?? $sale->created_at)->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select">
                            <option value="Tunai" {{ $sale->payment_method == 'Tunai' ? 'selected' : '' }}>Tunai</option>
                            <option value="QRIS" {{ $sale->payment_method == 'QRIS' ? 'selected' : '' }}>QRIS</option>
                            <option value="Transfer" {{ $sale->payment_method == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nominal Dibayar (Rp)</label>
                        <input type="number" name="paid_amount" class="form-control" 
                               value="{{ $sale->paid_amount }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="note" class="form-control" 
                               value="{{ $sale->note }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection