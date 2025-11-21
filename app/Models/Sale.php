<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';

    // sesuaikan dengan kolom di tabel sales Anda
    protected $fillable = [
        'total',
        'paid_amount',
        'payment_method',
        'note',
        'user_id',
        'sold_at'
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'total' => 'integer',
        'paid_amount' => 'integer',
    ];

    // relasi ke sale_items
    public function items()
    {
        return $this->hasMany(\App\Models\SaleItem::class, 'sale_id');
    }
}
