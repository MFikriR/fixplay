<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ps_units', 'is_active')) {
            Schema::table('ps_units', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('hourly_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ps_units', 'is_active')) {
            Schema::table('ps_units', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
