@extends('layouts.fixplay')

@section('page_title', 'Edit Transaksi')

@section('page_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-dark fw-bold">Edit Transaksi #{{ $sale->id }}</h4>
        <a href="{{ route('pos.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div>

    <div class="card card-dark">
        <div class="card-body">
            <form action="{{ route('sales.update', $sale->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="alert alert-info">
                    <strong>Info:</strong> Saat ini hanya dapat mengedit rincian pembayaran. 
                    Jika terjadi kesalahan input barang (jumlah/item), silakan 
                    <span class="text-danger fw-bold">Hapus Transaksi</span> ini di menu POS dan input ulang agar stok tetap akurat.
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Total Transaksi (Tidak dapat diubah)</label>
                        <input type="text" class="form-control bg-secondary text-white" 
                               value="Rp {{ number_format($sale->total_amount, 0, ',', '.') }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Waktu Transaksi</label>
                        <input type="text" class="form-control bg-secondary text-white" 
                               value="{{ $sale->created_at }}" disabled>
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
                               value="{{ $sale->paid_amount }}" min="{{ $sale->total_amount }}">
                        @error('paid_amount')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
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