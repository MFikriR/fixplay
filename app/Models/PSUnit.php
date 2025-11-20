<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PSUnit extends Model
{
    protected $table = 'ps_units'; // default, tapi eksplisit lebih aman
    protected $fillable = ['name','hourly_rate','is_active'];
    protected $casts = [
        'hourly_rate' => 'integer',
        'is_active' => 'boolean',
    ];
}