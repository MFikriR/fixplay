<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // gunakan jika kamu ingin UUID otomatis (opsional)

class GameSession extends Model
{
    // jika tabel di DB bernama 'sessions' (sepertinya demikian), pakai ini:
    protected $table = 'sessions';

    // jika primary key bukan auto-increment integer (mis. id string/uuid), atur:
    protected $keyType = 'string';
    public $incrementing = false;

    // jika tabelmu pakai created_at/updated_at, biarkan timestamps true (default)
    // protected $dates = ['start_time','end_time']; // opsional, laravel 10+ auto-casts timestamps

    // Isi fillable ini sesuaikan dengan kolom yang benar pada tabel sessions di DB kamu.
    // Saya sertakan kolom yang dipakai controller sebelumnya — ganti jika berbeda.
    protected $fillable = [
        'id',
        'ps_unit_id',
        'start_time',
        'end_time',
        'minutes',
        'extra_controllers',
        'arcade_controllers',
        'bill',
        'payment_method',
        'paid_amount',
        // 'name','unit','stock','price','active'  // hapus atau ganti sesuai skema
    ];

    // Relasi ke PSUnit (asumsi model PSUnit ada di App\Models\PSUnit)
    public function ps_unit()
    {
        return $this->belongsTo(PSUnit::class, 'ps_unit_id');
    }

    // Jika kamu menggunakan UUID otomatis ketika membuat model baru, uncomment:
    // use HasUuids;
}
