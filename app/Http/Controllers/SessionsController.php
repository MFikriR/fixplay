<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PSUnit;
use App\Models\GameSession;   // pakai model yang ada
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Str;



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
    // validasi minimal: terima numeric agar bisa 0.5 (30 menit)
    $data = $request->validate([
        'ps_unit_id' => 'required|exists:ps_units,id',
        'start_time' => 'required|date',
        'hours'      => 'required|numeric|min:0.5', // sebelumnya integer|min:1
        'extra_controllers' => 'nullable|integer|min:0',
        'arcade_controllers' => 'nullable|integer|min:0',
        'payment_method' => 'required|string',
        'paid_amount' => 'required|numeric|min:0',
    ]);

    $unit = PSUnit::findOrFail($data['ps_unit_id']);
    $hourly = (float) $unit->hourly_rate;

    $EX_RATE = 10000;
    $ARC_RATE = 15000;

    // pastikan numeric (float), karena request memberi string
    $hours = (float) $data['hours'];
    $extraControllers = (int) ($data['extra_controllers'] ?? 0);
    $arcadeControllers = (int) ($data['arcade_controllers'] ?? 0);

    // effective rate per hour
    $effectiveRatePerHour = $hourly + ($extraControllers * $EX_RATE) + ($arcadeControllers * $ARC_RATE);

    // hitung bill: jika ingin mengikuti peraturan pembulatan 30 menit ke atas per 1000,
    // ubah logika di sini. Untuk sekarang kita hitung sederhana:
    if ($hours === 0.5) {
        // contoh: setengah tarif; jika mau pembulatan, gunakan ceil ke 1000:
        // $bill = (int) (ceil(($effectiveRatePerHour * 0.5) / 1000) * 1000);
        $bill = (int) round($effectiveRatePerHour * 0.5); // tanpa pembulatan khusus
    } else {
        $bill = (int) round($effectiveRatePerHour * $hours);
    }

    // buat session (sesuaikan struktur tabelmu)
    // ... setelah perhitungan $bill, $minutesToAdd, dsb.

    $session = new Session();
    // isi id (UUID) karena kolom id di DB tidak auto-increment
    $session->id = (string) Str::uuid();

    $session->ps_unit_id = $unit->id;
    $session->start_time = Carbon::parse($data['start_time']);
    $minutesToAdd = (int) round($hours * 60);
    $session->end_time = Carbon::parse($data['start_time'])->addMinutes($minutesToAdd);

    $session->minutes = $minutesToAdd;
    $session->extra_controllers = $extraControllers;
    $session->arcade_controllers = $arcadeControllers;
    $session->bill = $bill;
    $session->payment_method = $data['payment_method'];
    $session->paid_amount = (float) $data['paid_amount'];

    // optional: isi user / ip / ua jika tersedia
    // optional: fill user / ip / ua
    $session->user_id    = auth()->id() ?? null;
    $session->ip_address = $request->ip();
    $session->user_agent = $request->userAgent();

    // minimal payload agar kolom NOT NULL tidak jadi masalah
    $session->payload = json_encode([
        'created_from' => 'web',
        'unit' => $unit->name ?? $unit->id,
        'hours' => $hours,
    ]);

    // set last activity (kolom int) — wajib karena DB menolak jika kosong
    $session->last_activity = (int) time();

    // simpan
    $session->save();



    // lakukan tindakan lain (catat penjualan, cetak struk, dsb.)
    return redirect()->route('sessions.index')->with('success', 'Sesi berhasil dibuat.');
}


    public function destroy($sid)
    {
        $s = GameSession::findOrFail($sid);
        $s->delete();
        return redirect()->route('sessions.index')->with('success', 'Riwayat sesi dihapus.');
    }
}
