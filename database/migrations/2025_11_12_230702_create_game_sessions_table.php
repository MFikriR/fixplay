<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ps_unit_id')->constrained('ps_units');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('extra_stick')->default(0);
            $table->integer('extra_arcade')->default(0);
            $table->integer('bill')->default(0);
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete(); // link ke penjualan saat checkout
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
