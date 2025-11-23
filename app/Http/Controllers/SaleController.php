<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function show($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            abort(404);
        }

        $items = DB::table('sale_items')
            ->where('sale_id', $sale->id)
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->select('sale_items.*', 'products.name as product_name')
            ->get();

        // --- PERBAIKAN: LOGIKA TOMBOL KEMBALI DINAMIS ---
        // Ambil URL sebelumnya
        $backUrl = url()->previous();
        
        // Jika URL sebelumnya sama dengan URL saat ini (misal karena refresh),
        // atau jika URL sebelumnya kosong, arahkan default ke Dashboard.
        if ($backUrl == url()->current() || empty($backUrl)) {
            $backUrl = route('dashboard');
        }

        return view('sales.receipt', [
            'sale' => $sale,
            'items' => $items,
            'backUrl' => $backUrl // Kirim variabel ini ke View
        ]);
    }

    public function edit($id)
    {
        // Pastikan memuat relasi items agar bisa diedit harganya
        $sale = Sale::with('items')->findOrFail($id);

        return view('sales.edit', [
            'sale' => $sale
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'created_at'     => 'required|date',          // Waktu Transaksi
            'total_bill'     => 'required|numeric|min:0', // Total Tagihan Baru
            'payment_method' => 'required|string',
            'paid_amount'    => 'required|numeric|min:0',
            'note'           => 'nullable|string|max:255',
        ]);

        $sale = Sale::with('items')->findOrFail($id);
        $newTotal = $request->total_bill;

        // Cek apakah uang yang dibayar kurang dari tagihan baru
        if ($request->paid_amount < $newTotal) {
            return back()->withErrors(['paid_amount' => 'Nominal dibayar kurang dari total tagihan baru!']);
        }

        DB::transaction(function () use ($sale, $request, $newTotal) {
            // 1. Update Waktu Transaksi
            $sale->sold_at = $request->created_at;
            $sale->created_at = $request->created_at;

            // 2. Update Harga Item (PENTING AGAR DASHBOARD & LAPORAN BERUBAH)
            if ($sale->items->isNotEmpty()) {
                $item = $sale->items->first();
                $item->subtotal = $newTotal;
                
                // Update harga satuan juga agar konsisten (hindari pembagian nol)
                $qty = max($item->qty, 1);
                $item->unit_price = $newTotal / $qty;
                
                $item->save();
            }

            // 3. Update Header Sales
            $sale->total = $newTotal; 
            
            // 4. Update Pembayaran & Kembalian
            $sale->payment_method = $request->payment_method;
            $sale->paid_amount    = $request->paid_amount;
            $sale->change_amount  = $request->paid_amount - $newTotal;
            $sale->note           = $request->note;

            $sale->save();

            // 5. SINKRONISASI KE SESSION (PERBAIKAN UTAMA)
            // Cari sesi yang terhubung dengan ID penjualan ini, lalu update kolom 'bill'
            DB::table('sessions')
                ->where('sale_id', $sale->id)
                ->update(['bill' => $newTotal]);
        });

        return back()->with('success', 'Data transaksi berhasil diperbarui (Waktu & Harga).');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $sale = Sale::findOrFail($id);
            $items = DB::table('sale_items')->where('sale_id', $sale->id)->get();

            foreach ($items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->increment('stock', $item->qty);
                }
            }

            DB::table('sale_items')->where('sale_id', $sale->id)->delete();
            $sale->delete();
        });

        return back()->with('success', 'Transaksi dihapus dan stok dikembalikan.');
    }
}