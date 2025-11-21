<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session; // Pastikan model Session ada
use App\Models\PSUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SessionsController extends Controller
{
    public function index()
    {
        $units = PSUnit::all();
        // Ambil sesi yang sudah selesai (closed) untuk riwayat
        $closed_sessions = Session::with('ps_unit')
            ->whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(20)
            ->get();

        return view('sessions', compact('units', 'closed_sessions'));
    }

    public function storeFixed(Request $request)
    {
        $request->validate([
            'ps_unit_id' => 'required',
            'start_time' => 'required|date',
            'hours'      => 'required|numeric',
            'paid_amount'=> 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            $unit = PSUnit::findOrFail($request->ps_unit_id);
            $start = Carbon::parse($request->start_time);
            $hours = (float)$request->hours;
            $end   = $start->copy()->addMinutes($hours * 60);

            // 1. Hitung Tagihan
            $baseRate = $unit->hourly_rate;
            $extraRate = 10000; // Rate stik tambahan
            $arcadeRate = 15000; // Rate arcade

            $baseTotal   = $baseRate * $hours;
            $extraTotal  = ($request->extra_controllers ?? 0) * $extraRate * $hours;
            $arcadeTotal = ($request->arcade_controllers ?? 0) * $arcadeRate * $hours;
            
            $totalBill = $baseTotal + $extraTotal + $arcadeTotal;

            // Pembulatan khusus 30 menit (opsional, sesuaikan logika JS Anda)
            if ($hours == 0.5) {
                $totalBill = ceil($totalBill / 1000) * 1000;
            }

            // 2. Buat Data Penjualan (Agar Masuk Laporan)
            $sale = Sale::create([
                'sold_at'        => $end, // Pendapatan dihitung saat sesi selesai
                'total_amount'   => $totalBill,
                'paid_amount'    => $request->paid_amount,
                'change_amount'  => max(0, $request->paid_amount - $totalBill),
                'payment_method' => $request->payment_method,
                'note'           => "Sesi PS: {$unit->name} ({$hours} jam)",
            ]);

            // Masukkan sebagai item penjualan (Type: Service/PS)
            // Pastikan tabel sale_items mendukung kolom ini, atau sesuaikan
            DB::table('sale_items')->insert([
                'sale_id'    => $sale->id,
                'product_id' => null, // Bukan produk fisik
                'qty'        => 1,
                'unit_price' => $totalBill,
                'subtotal'   => $totalBill,
            ]);

            // 3. Simpan Data Sesi
            // PENTING: Simpan sale_id agar bisa dihapus nanti
            $session = new Session();
            $session->ps_unit_id = $unit->id;
            $session->sale_id    = $sale->id; // KUNCI KONEKSI
            $session->start_time = $start;
            $session->end_time   = $end;
            $session->minutes    = $hours * 60;
            $session->bill       = $totalBill;
            $session->extra_controllers = $request->extra_controllers ?? 0;
            $session->arcade_controllers = $request->arcade_controllers ?? 0;
            $session->status     = 'closed';
            $session->save();
        });

        return redirect()->route('sessions.index')->with('success', 'Sesi berhasil dibuat dan ditagihkan.');
    }

    public function destroy($sid)
    {
        // Gunakan Transaction agar aman
        DB::transaction(function () use ($sid) {
            $session = Session::findOrFail($sid);

            // 1. Hapus Data Penjualan Terkait (Agar Laporan Berkurang)
            if ($session->sale_id) {
                $sale = Sale::find($session->sale_id);
                if ($sale) {
                    // Hapus item penjualan dulu
                    DB::table('sale_items')->where('sale_id', $sale->id)->delete();
                    // Hapus header penjualan
                    $sale->delete();
                }
            }

            // 2. Hapus Sesi
            $session->delete();
        });

        return redirect()->route('sessions.index')->with('success', 'Riwayat sesi dan laporan pendapatan terkait berhasil dihapus.');
    }
}