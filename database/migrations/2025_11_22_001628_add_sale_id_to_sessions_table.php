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
        Schema::table('sessions', function (Blueprint $table) {
            // Menambahkan kolom sale_id yang boleh kosong (nullable)
            // dan terhubung ke tabel sales (constrained)
            // Jika data penjualan dihapus, sale_id di sini jadi NULL (nullOnDelete)
            $table->foreignId('sale_id')
                  ->nullable()
                  ->after('ps_unit_id') // Menaruh kolom setelah ps_unit_id agar rapi
                  ->constrained('sales')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};