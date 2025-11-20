<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Product::insert([
            ['name' => 'Mie',    'unit'=>'pcs','stock'=>50,'price'=>10000,'active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name' => 'Rosta',  'unit'=>'pcs','stock'=>40,'price'=>10000,'active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name' => 'Es Batu','unit'=>'kg', 'stock'=>10,'price'=>15000,'active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);

        \App\Models\PsUnit::insert([
            ['name'=>'PS 4','hourly_rate'=>10000,'is_vip'=>false,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'PS 5 VIP','hourly_rate'=>25000,'is_vip'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
