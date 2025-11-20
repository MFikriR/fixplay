<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products'; // default
    protected $fillable = ['name','category','price','stock','unit'];
    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
    ];
}
