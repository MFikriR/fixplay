<?php
// app/Http/Controllers/PurchasesController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Purchase;        // jika ada model Purchase
use App\Models\PurchaseItem;    // jika ada model PurchaseItem
use App\Models\Expense;
use Carbon\Carbon;

class PurchasesController extends Controller
{
    public function store(Request $request)
    {
        // contoh validasi dasar; sesuaikan aturan dengan form Anda
        $data = $request->validate([
            'supplier' => 'nullable|string|max:191',
            'items'    => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'note'     => 'nullable|string|max:1000',
            'timestamp'=> 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $ts = $data['timestamp'] ?? now();

            // --- buat purchase (jika Anda punya tabel purchases) ---
            // jika tidak punya, Anda bisa skip membuat Purchase dan langsung buat Expense + update stok
            $purchase = Purchase::create([
                'supplier' => $data['supplier'] ?? null,
                'note' => $data['note'] ?? null,
                'purchased_at' => $ts,
                // tambahkan kolom lain bila perlu
            ]);

            $totalAmount = 0;
            $descLines = [];

            foreach ($data['items'] as $it) {
                $pid = $it['product_id'];
                $qty = (int)$it['qty'];
                $price = (int)$it['price'];
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                // buat purchase item record jika model/table ada
                if (class_exists(\App\Models\PurchaseItem::class)) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $pid,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);
                }

                // update stok produk: tambah stok
                $product = Product::find($pid);
                if ($product) {
                    $product->stock = ($product->stock ?? 0) + $qty;
                    $product->save();
                    $descLines[] = $product->name . ' ×' . $qty;
                } else {
                    $descLines[] = "PID{$pid} ×{$qty}";
                }
            }

            // --- catat pengeluaran otomatis ke tabel expenses ---
            $expense = Expense::create([
                'category'    => 'Beli Stock',
                'description' => ($data['supplier'] ? $data['supplier'].' — ' : '') . implode(', ', $descLines),
                'amount'      => $totalAmount,
                'timestamp'   => $ts,
            ]);

            DB::commit();

            // redirect ke laporan agar user bisa melihat efeknya
            return redirect()->route('reports.index')
                ->with('success', 'Pembelian tersimpan. Pengeluaran tercatat dan stok diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("PurchasesController@store error: ".$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
