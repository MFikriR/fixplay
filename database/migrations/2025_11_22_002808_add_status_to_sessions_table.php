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
            // Cek apakah kolom status sudah ada atau belum
            if (!Schema::hasColumn('sessions', 'status')) {
                // Tambahkan kolom status, default 'active'
                $table->string('status')->default('active')->after('bill');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};