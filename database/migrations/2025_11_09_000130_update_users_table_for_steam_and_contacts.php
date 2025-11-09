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
        Schema::table('users', function (Blueprint $table) {
            $table->string('steam_id')->nullable()->unique()->after('authentik_id');
            $table->foreignId('tenant_contact_id')->nullable()->after('steam_id')->constrained('tenant_contacts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_contact_id');
            $table->dropUnique(['steam_id']);
            $table->dropColumn('steam_id');
        });
    }
};
