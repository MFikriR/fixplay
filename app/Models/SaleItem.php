<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';

    // bila ingin mass assign:
    protected $fillable = [
        'sale_id','product_id','description','qty','unit_price','subtotal'
    ];

    // RELATIONS
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product()
    {
        // product_id bisa null (untuk sesi PS), jadi belongsTo tetap benar
        return $this->belongsTo(Product::class, 'product_id');
    }
}
