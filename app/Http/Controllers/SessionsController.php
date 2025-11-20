<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PSUnit;
use App\Models\GameSession;   // pakai model yang ada
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;



class SessionsController extends Controller
{
    public function index()
    {
        $units = PSUnit::all();
        $closed_sessions = GameSession::with('ps_unit')
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->limit(20)
            ->get();

        return view('sessions', [
            'units' => $units,
            'closed_sessions' => $closed_sessions,
        ]);
    }

    public function storeFixed(Request $request)
    {
        $data = $request->validate([
            'ps_unit_id' => 'required|exists:ps_units,id',
            'start_time' => 'required|date',
            'hours'      => 'required|numeric|min:0.5', // bisa desimal (30 menit = 0.5)
            'extra_controllers' => 'nullable|integer|min:0',
            'arcade_controllers' => 'nullable|integer|min:0',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $unit = \App\Models\PSUnit::findOrFail($data['ps_unit_id']);
        $hourly = (float) $unit->hourly_rate;
        $EX_RATE = 10000;
        $ARC_RATE = 15000;

        $extraControllers = (int) ($data['extra_controllers'] ?? 0);
        $arcadeControllers = (int) ($data['arcade_controllers'] ?? 0);
        $hours = (float) $data['hours'];

        // hitung effective per jam, lalu bill (boleh jam desimal)
        $effectiveRatePerHour = $hourly + ($extraControllers * $EX_RATE) + ($arcadeControllers * $ARC_RATE);

        // jika jam = 0.5 => bill = effectiveRatePerHour * 0.5
        $bill = (int) round($effectiveRatePerHour * $hours);

        // minutes to add (untuk end_time)
        $minutesToAdd = (int) round($hours * 60);

        // siapkan id session (UUID karena tabel sessions punya id varchar)
        $sessionId = (string) Str::uuid();
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            // insert ke tabel sessions (pastikan semua kolom non-null di DB diberikan)
            DB::table('sessions')->insert([
                'id' => $sessionId,
                'ps_unit_id' => $unit->id,
                'start_time' => Carbon::parse($data['start_time'])->format('Y-m-d H:i:s'),
                'end_time'   => Carbon::parse($data['start_time'])->addMinutes($minutesToAdd)->format('Y-m-d H:i:s'),
                'minutes'    => $minutesToAdd,
                'extra_controllers' => $extraControllers,
                'arcade_controllers' => $arcadeControllers,
                'bill'       => $bill,
                'payment_method' => $data['payment_method'],
                'paid_amount' => (int)$data['paid_amount'],
                // optional fields — sesuaikan jika tidak ada di DB
                'user_id' => auth()->id() ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => json_encode([
                    'created_from' => 'web',
                    'unit' => $unit->name ?? $unit->id,
                    'hours' => $hours,
                    'extra_controllers' => $extraControllers,
                    'arcade_controllers' => $arcadeControllers,
                ]),
                'last_activity' => time(), // pastikan kolom ini ada (menghindari error no default)
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // buat record sale (penjualan) — sale untuk sesi (product_id = NULL)
            $saleId = DB::table('sales')->insertGetId([
                'sold_at' => $now,
                'total' => $bill,
                'payment_method' => $data['payment_method'],
                'paid_amount' => (int)$data['paid_amount'],
                'note' => 'Sesi '.$unit->name.' - '.$hours.' jam',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // buat sale_item untuk sesi (product_id NULL)
            $description = "Sesi {$unit->name} - {$hours} jam";
            DB::table('sale_items')->insert([
                'sale_id' => $saleId,
                'product_id' => null,
                'description' => $description,
                'qty' => 1,
                'unit_price' => $bill,
                'subtotal' => $bill,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // (optional) kalau ingin catat transaksi produk / update stock, lakukan di sini

            DB::commit();

            // siapkan object $sale untuk view (sama format dengan blade receipt)
            $sale = (object) [
                'id' => $saleId,
                'timestamp' => $now,
                'total' => $bill,
                'payment_method' => $data['payment_method'],
                'paid_amount' => (int)$data['paid_amount'],
                'change_amount' => max(0, (int)$data['paid_amount'] - $bill),
                'note' => $description,
                'items' => [
                    (object)[
                        'product' => null,
                        'description' => $description,
                        'qty' => 1,
                        'unit_price' => $bill,
                        'subtotal' => $bill,
                    ]
                ],
            ];

            // tampilkan struk (view yang sudah saya berikan: resources/views/sales/receipt.blade.php)
            return view('sales.receipt', compact('sale'));

        } catch (\Throwable $e) {
            DB::rollBack();
            // log error dan kembalikan pesan
            \Log::error('storeFixed error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => 'Gagal membuat sesi: '.$e->getMessage()]);
        }
    }


    public function destroy($sid)
    {
        $s = GameSession::findOrFail($sid);
        $s->delete();
        return redirect()->route('sessions.index')->with('success', 'Riwayat sesi dihapus.');
    }
}
