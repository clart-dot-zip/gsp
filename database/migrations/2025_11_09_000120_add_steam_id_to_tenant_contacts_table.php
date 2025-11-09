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
        Schema::table('tenant_contacts', function (Blueprint $table) {
            $table->string('steam_id')->nullable()->unique()->after('contact_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_contacts', function (Blueprint $table) {
            $table->dropUnique(['steam_id']);
            $table->dropColumn('steam_id');
        });
    }
};
