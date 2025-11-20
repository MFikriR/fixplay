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
        Schema::create('sessions', function (Blueprint $table) {
            // Jika kamu ingin id string/uuid (sesuai log yang muncul sebelumnya), gunakan string primary
            $table->string('id')->primary();

            // Relasi ke tabel unit PS (sesuaikan tipe id ps_units; umumnya unsignedBigInteger)
            $table->unsignedBigInteger('ps_unit_id')->nullable();

            // Waktu mulai & selesai sesi
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            // Durasi dalam menit
            $table->integer('minutes')->nullable();

            // tambahan stik / arcade
            $table->integer('extra_controllers')->default(0);
            $table->integer('arcade_controllers')->default(0);

            // tagihan dan pembayaran
            $table->bigInteger('bill')->default(0);
            $table->string('payment_method')->nullable();
            $table->bigInteger('paid_amount')->default(0);

            // catatan opsional lain
            $table->text('notes')->nullable();

            // timestamps standar (created_at, updated_at)
            $table->timestamps();

            // Index / foreign key (opsional)
            // Jika tabel ps_units ada dan kolom id bertipe unsignedBigInteger, aktifkan constraint di bawah.
            // Kalau tidak yakin, hapus baris foreign key atau biarkan sebagai nullable kolom biasa.
            //
            // $table->foreign('ps_unit_id')
            //       ->references('id')->on('ps_units')
            //       ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
