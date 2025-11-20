<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';

    // Karena id adalah varchar dan kita akan mengisi UUID:
    public $incrementing = false;
    protected $keyType = 'string';

    // timestamps ada di tabel (created_at / updated_at)
    public $timestamps = true;

    protected $fillable = [
    'id','ps_unit_id','start_time','end_time','minutes',
    'extra_controllers','arcade_controllers','bill',
    'payment_method','paid_amount','user_id','ip_address',
    'user_agent','payload','last_activity'
    ];

    protected $casts = [
    'start_time'=>'datetime','end_time'=>'datetime',
    'minutes'=>'integer','last_activity'=>'integer',
    'bill'=>'integer','paid_amount'=>'integer'
    ];
}
