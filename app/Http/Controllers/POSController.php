<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;   // ganti jika model berbeda
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class POSController extends Controller
{
    public function index()
    {
        // ambil produk yang aktif / dengan stok > 0 (sesuaikan query)
        $products = Product::select('id','name','price','stock')->orderBy('name')->get();
        return view('pos', compact('products'));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|integer|distinct',
            'qty' => 'required|array',
            'qty.*' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        $productIds = $data['product_id'];
        $qtys = $data['qty'];

        DB::beginTransaction();
        try {
            // lock rows for update to avoid race condition
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $total = 0;
            // validate stocks
            foreach ($productIds as $i => $pid) {
                $qty = intval($qtys[$i] ?? 0);
                if (!isset($products[$pid])) {
                    throw ValidationException::withMessages(['product_id' => "Produk dengan ID {$pid} tidak ditemukan."]);
                }
                $p = $products[$pid];
                if ($qty > $p->stock) {
                    throw ValidationException::withMessages(['stock' => "Stok untuk {$p->name} tidak cukup."]);
                }
                $total += $p->price * $qty;
            }

            if (floatval($data['paid_amount']) < $total) {
                throw ValidationException::withMessages(['paid_amount' => "Nominal dibayar kurang dari total Rp {$total}."]);
            }

            // Simpan transaksi — struktur tabel sales/order mungkin berbeda di projectmu.
            // Contoh sederhana: simpan ke tabel `sales` dan `sale_items`.
            $sale = DB::table('sales')->insertGetId([
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'paid_amount' => $data['paid_amount'],
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // kurangi stok dan simpan item
            foreach ($productIds as $i => $pid) {
                $qty = intval($qtys[$i] ?? 0);
                $p = $products[$pid];

                // insert sale_items (jika tabel ada)
                DB::table('sale_items')->insert([
                    'sale_id' => $sale,
                    'product_id' => $pid,
                    'price' => $p->price,
                    'qty' => $qty,
                    'subtotal' => $p->price * $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // update stok
                $p->decrement('stock', $qty);
            }

            DB::commit();
            return redirect()->route('pos.index')->with('success', 'Transaksi berhasil. Total: Rp ' . number_format($total,0,',','.'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            DB::rollBack();
            throw $ve;
        } catch (\Throwable $th) {
            DB::rollBack();
            // log error jika perlu
            return redirect()->back()->with('error', 'Gagal memproses transaksi: ' . $th->getMessage());
        }
    }
}
