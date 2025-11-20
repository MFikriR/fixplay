<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            // jika id sudah string/uuid, biarkan; jika belum, jangan ubah primary key di sini
            if (!Schema::hasColumn('sessions', 'ps_unit_id')) {
                $table->unsignedBigInteger('ps_unit_id')->nullable()->after('id');
                // jika table ps_units ada dan ingin FK:
                // $table->foreign('ps_unit_id')->references('id')->on('ps_units')->onDelete('set null');
            }

            if (!Schema::hasColumn('sessions', 'start_time')) {
                $table->timestamp('start_time')->nullable()->after('ps_unit_id');
            }

            if (!Schema::hasColumn('sessions', 'end_time')) {
                $table->timestamp('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('sessions', 'minutes')) {
                $table->integer('minutes')->nullable()->after('end_time');
            }

            if (!Schema::hasColumn('sessions', 'extra_controllers')) {
                $table->integer('extra_controllers')->default(0)->after('minutes');
            }

            if (!Schema::hasColumn('sessions', 'arcade_controllers')) {
                $table->integer('arcade_controllers')->default(0)->after('extra_controllers');
            }

            if (!Schema::hasColumn('sessions', 'bill')) {
                $table->bigInteger('bill')->default(0)->after('arcade_controllers');
            }

            if (!Schema::hasColumn('sessions', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('bill');
            }

            if (!Schema::hasColumn('sessions', 'paid_amount')) {
                $table->bigInteger('paid_amount')->default(0)->after('payment_method');
            }
        });
    }

    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            // hati-hati: down akan menghapus kolom
            $cols = [
                'ps_unit_id','start_time','end_time','minutes',
                'extra_controllers','arcade_controllers','bill','payment_method','paid_amount'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('sessions', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
