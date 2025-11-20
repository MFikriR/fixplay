<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // tambahkan kolom timestamp nullable (jika ingin bernama berbeda, ubah 'timestamp')
            if (! Schema::hasColumn('expenses', 'timestamp')) {
                $table->timestamp('timestamp')->nullable()->after('amount');
            }
        });

        // (opsional) — backfill timestamp dari created_at jika ingin
        // DB::table('expenses')->whereNull('timestamp')->update(['timestamp' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'timestamp')) {
                $table->dropColumn('timestamp');
            }
        });
    }
};
