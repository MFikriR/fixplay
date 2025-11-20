<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expenses';
    protected $fillable = ['category','description','amount','timestamp'];
    protected $casts = [
        'amount' => 'integer',
        'timestamp' => 'datetime',
    ];

    public $timestamps = true; // created_at/updated_at
}
