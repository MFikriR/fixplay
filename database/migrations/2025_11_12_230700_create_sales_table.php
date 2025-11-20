<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->dateTime('sold_at')->useCurrent();        // waktu penjualan
            $table->integer('total')->default(0);
            $table->string('payment_method')->nullable();     // Tunai/QRIS/Transfer
            $table->integer('paid_amount')->nullable();
            $table->integer('change_amount')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
