<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sessions', 'created_at') && ! Schema::hasColumn('sessions', 'updated_at')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->timestamps(); // menambah created_at & updated_at (nullable)
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sessions', 'created_at') || Schema::hasColumn('sessions', 'updated_at')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
    }
};
