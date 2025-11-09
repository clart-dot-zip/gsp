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
        Schema::create('tenant_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('tenant_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('tenant_group_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_group_id')->constrained('tenant_groups')->cascadeOnDelete();
            $table->foreignId('tenant_permission_id')->constrained('tenant_permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_group_id', 'tenant_permission_id'], 'tg_permission_unique');
        });

        Schema::create('tenant_group_inheritances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_group_id')->constrained('tenant_groups')->cascadeOnDelete();
            $table->foreignId('child_group_id')->constrained('tenant_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['parent_group_id', 'child_group_id'], 'tg_inherit_unique');
        });

        Schema::create('tenant_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('steam_id')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'steam_id']);
        });

        Schema::create('tenant_player_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_group_id')->constrained('tenant_groups')->cascadeOnDelete();
            $table->foreignId('tenant_player_id')->constrained('tenant_players')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_group_id', 'tenant_player_id'], 'tg_player_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_player_group');
        Schema::dropIfExists('tenant_players');
    Schema::dropIfExists('tenant_group_inheritances');
        Schema::dropIfExists('tenant_group_permission');
        Schema::dropIfExists('tenant_permissions');
        Schema::dropIfExists('tenant_groups');
    }
};
