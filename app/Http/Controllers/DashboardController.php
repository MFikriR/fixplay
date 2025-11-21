<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tz = 'Asia/Makassar';

        // === Kartu ringkasan hari ini (PS vs Produk) ===
        $start = Carbon::today($tz)->startOfDay();
        $end   = Carbon::today($tz)->endOfDay();

        $totals = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw("
               COALESCE(SUM(CASE WHEN sale_items.product_id IS NULL THEN sale_items.subtotal ELSE 0 END),0) AS ps,
               COALESCE(SUM(CASE WHEN sale_items.product_id IS NOT NULL THEN sale_items.subtotal ELSE 0 END),0) AS prod
            ")
            ->whereBetween('sales.sold_at', [$start, $end])
            ->first();

        $todayPs    = (int) ($totals->ps   ?? 0);
        $todayProd  = (int) ($totals->prod ?? 0);
        $todayTotal = $todayPs + $todayProd;

        // === Grafik 10 hari terakhir (total penjualan per hari) ===
        $daysBack    = 10;
        $chartStart  = Carbon::today($tz)->subDays($daysBack - 1)->startOfDay();

        // PERBAIKAN GRAFIK: Gunakan SUM(sale_items.subtotal) karena tabel sales tidak punya kolom total
        $rows = Sale::selectRaw('DATE(sales.sold_at) AS d, SUM(sale_items.subtotal) AS t')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id') // Join ke items untuk ambil total
            ->whereBetween('sales.sold_at', [$chartStart, $end])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r->d] = (int) $r->t;
        }

        $chartLabels = [];
        $chartData   = [];
        for ($i = 0; $i < $daysBack; $i++) {
            $d = $chartStart->copy()->addDays($i);
            $key = $d->toDateString();
            $chartLabels[] = $d->format('d-m');
            $chartData[]   = $byDate[$key] ?? 0;
        }

        // === Riwayat transaksi terakhir (10) ===
        $last = Sale::with(['items.product'])
            ->orderBy('sold_at', 'desc')
            ->limit(10)
            ->get();

        $lastTx = [];
        foreach ($last as $s) {
            $names = [];
            
            foreach ($s->items->take(3) as $it) {
                // Hanya ambil nama jika Produk fisik.
                if ($it->product) {
                    $names[] = $it->product->name;
                }
            }

            // Logika Judul (Rental mengambil dari Note)
            $title = !empty($names) ? implode(', ', $names) : ($s->note ?: 'Item');

            if ($s->items->count() > 3) {
                $title .= ' +' . ($s->items->count() - 3) . ' item';
            }

            // PERBAIKAN TOTAL DI SINI:
            // Ambil total dari kolom 'total' atau 'total_amount' jika ada.
            // JIKA 0 (karena tabel sales tidak menyimpan total), HITUNG dari jumlah subtotal item.
            $totalFix = $s->total ?? $s->total_amount ?? 0;
            
            if ($totalFix == 0) {
                $totalFix = $s->items->sum('subtotal');
            }

            $lastTx[] = [
                'id'    => $s->id,
                'date'  => Carbon::parse($s->sold_at, $tz)->format('d-m-Y H:i'),
                'total' => (int) $totalFix,
                'title' => $title,
            ];
        }

        return view('dashboard', compact(
            'todayPs', 'todayProd', 'todayTotal',
            'chartLabels', 'chartData', 'lastTx'
        ));
    }
}