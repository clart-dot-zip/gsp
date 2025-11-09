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
        Schema::create('tenant_support_ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_support_ticket_id')
                ->constrained('tenant_support_tickets')
                ->cascadeOnDelete();
            $table->morphs('author');
            $table->longText('body')->nullable();
            $table->boolean('is_resolution')->default(false);
            $table->unsignedInteger('timer_seconds')->nullable();
            $table->timestamp('timer_started_at')->nullable();
            $table->timestamp('timer_stopped_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_support_ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_support_ticket_notes');
    }
};
