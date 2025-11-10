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
        Schema::create('tenant_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_player_id')->nullable()->constrained('tenant_players')->nullOnDelete();
            $table->string('player_name');
            $table->string('player_steam_id', 32)->nullable();
            $table->string('length_code', 16)->default('0');
            $table->text('reason');
            $table->text('admin_reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_contact_id')->nullable()->constrained('tenant_contacts')->nullOnDelete();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'banned_at']);
            $table->index(['tenant_id', 'player_name']);
            $table->index(['tenant_id', 'player_steam_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_bans');
    }
};
