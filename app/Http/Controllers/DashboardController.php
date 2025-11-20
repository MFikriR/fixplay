<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        $rows = Sale::selectRaw('DATE(sold_at) AS d, SUM(total) AS t')
            ->whereBetween('sold_at', [$chartStart, $end])
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
                $names[] = $it->product->name ?? ($it->description ?? 'Item');
            }
            $title = $names ? implode(', ', $names) : ($s->note ?: 'Penjualan');
            if ($s->items->count() > 3) {
                $title .= ' +' . ($s->items->count() - 3) . ' item';
            }

            $lastTx[] = [
                'id'    => $s->id,
                'date'  => Carbon::parse($s->sold_at, $tz)->format('d-m-Y H:i'),
                'total' => (int) ($s->total ?? 0),
                'title' => $title,
            ];
        }

        return view('dashboard', compact(
            'todayPs', 'todayProd', 'todayTotal',
            'chartLabels', 'chartData', 'lastTx'
        ));
    }
}
