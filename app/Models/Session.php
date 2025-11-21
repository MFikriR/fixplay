<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. PENTING: Import ini

class Session extends Model
{
    use HasUuids; // 2. PENTING: Aktifkan ini agar ID terisi otomatis (UUID)

    protected $table = 'sessions';

    // Karena id adalah varchar (UUID):
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    // 3. Gunakan guarded kosong agar semua kolom (termasuk sale_id, status) bisa diisi
    // Ini lebih aman daripada $fillable yang sering terlupa diupdate
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'minutes'    => 'integer',
        'bill'       => 'integer',
        'paid_amount'=> 'integer'
    ];

    // 4. Relasi ke Unit PS (PENTING: Dipakai di halaman index)
    public function ps_unit()
    {
        return $this->belongsTo(PSUnit::class, 'ps_unit_id');
    }

    // 5. Relasi ke Penjualan/Keuangan (PENTING: Dipakai saat menghapus sesi)
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}