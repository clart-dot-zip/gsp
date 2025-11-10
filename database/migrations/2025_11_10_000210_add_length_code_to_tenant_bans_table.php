<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_bans', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_bans', 'length_code')) {
                $table->string('length_code', 16)->default('0')->after('player_steam_id');
            }
        });

        DB::table('tenant_bans')
            ->whereNull('length_code')
            ->update(['length_code' => '0']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_bans', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_bans', 'length_code')) {
                $table->dropColumn('length_code');
            }
        });
    }
};
