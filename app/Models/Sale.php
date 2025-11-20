<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['name','unit','stock','price','active'];
    protected $casts = ['sold_at' => 'datetime'];
    public function items(){ return $this->hasMany(\App\Models\SaleItem::class); }

}
