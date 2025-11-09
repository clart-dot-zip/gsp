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
        Schema::create('tenant_support_ticket_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_support_ticket_id')
                ->constrained('tenant_support_tickets')
                ->cascadeOnDelete();
            $table->foreignId('tenant_player_id')
                ->constrained('tenant_players')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique([
                'tenant_support_ticket_id',
                'tenant_player_id',
            ], 'ticket_player_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_support_ticket_players');
    }
};
