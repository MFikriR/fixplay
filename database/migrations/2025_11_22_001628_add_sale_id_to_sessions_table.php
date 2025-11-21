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
            // Menambahkan kolom sale_id setelah ps_unit_id
            // Nullable: agar tidak error untuk data lama yang sudah ada
            $table->unsignedBigInteger('sale_id')->nullable()->after('ps_unit_id');
            
            // Opsional: Menambahkan index agar pencarian lebih cepat saat menghapus
            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('sale_id');
        });
    }
};