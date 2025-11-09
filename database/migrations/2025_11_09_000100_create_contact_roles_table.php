<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('contact_roles')->insert([
            ['name' => 'Primary Contact', 'description' => 'Main operational point of contact', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Escalation Manager', 'description' => 'Escalation lead for incidents', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Billing', 'description' => 'Finance and invoicing contact', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_roles');
    }
};
