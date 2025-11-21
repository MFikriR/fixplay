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
            // Ubah kolom bawaan Laravel agar bisa kosong (nullable)
            // Ini penting karena kita memakai tabel ini untuk Rental PS
            
            if (Schema::hasColumn('sessions', 'payload')) {
                $table->longText('payload')->nullable()->change();
            }
            
            if (Schema::hasColumn('sessions', 'last_activity')) {
                $table->integer('last_activity')->nullable()->change();
            }

            // Kolom bawaan lain yang mungkin ada (jaga-jaga)
            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
            if (Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->change();
            }
            if (Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu dikembalikan karena nullable lebih aman
    }
};