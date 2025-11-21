<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Menampilkan detail struk penjualan.
     */
    public function show($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            abort(404);
        }

        // Mengambil item penjualan dengan join ke produk untuk nama
        $items = DB::table('sale_items')
            ->where('sale_id', $sale->id)
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->select('sale_items.*', 'products.name as product_name')
            ->get();

        return view('sales.receipt', [
            'sale' => $sale,
            'items' => $items
        ]);
    }

    /**
     * Menampilkan form edit penjualan.
     * (Fokus pada edit metode bayar, catatan, dan nominal)
     */
    public function edit($id)
    {
        $sale = Sale::findOrFail($id);

        return view('sales.edit', [
            'sale' => $sale
        ]);
    }

    /**
     * Memproses update data penjualan.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'paid_amount'    => 'required|numeric|min:0',
            'note'           => 'nullable|string|max:255',
        ]);

        $sale = Sale::findOrFail($id);

        // Validasi sederhana: Bayar tidak boleh kurang dari total
        if ($request->paid_amount < $sale->total_amount) {
            return back()->withErrors(['paid_amount' => 'Nominal dibayar kurang dari total transaksi!']);
        }

        // Hitung ulang kembalian
        $change_amount = $request->paid_amount - $sale->total_amount;

        $sale->update([
            'payment_method' => $request->payment_method,
            'paid_amount'    => $request->paid_amount,
            'change_amount'  => $change_amount,
            'note'           => $request->note,
        ]);

        return redirect()->route('pos.index')->with('success', 'Data transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi dan MENGEMBALIKAN STOK (Restock).
     */
    public function destroy($id)
    {
        // Gunakan DB Transaction agar jika gagal di tengah, data tidak rusak
        DB::transaction(function () use ($id) {
            $sale = Sale::findOrFail($id);

            // 1. Ambil semua item dari transaksi ini
            $items = DB::table('sale_items')->where('sale_id', $sale->id)->get();

            // 2. Kembalikan stok untuk setiap produk
            foreach ($items as $item) {
                if ($item->product_id) {
                    // Increment stok produk
                    Product::where('id', $item->product_id)
                        ->increment('stock', $item->qty);
                }
            }

            // 3. Hapus Item Penjualan
            DB::table('sale_items')->where('sale_id', $sale->id)->delete();

            // 4. Hapus Header Penjualan
            $sale->delete();
        });

        return redirect()->route('pos.index')->with('success', 'Transaksi dihapus dan stok produk telah dikembalikan.');
    }
}