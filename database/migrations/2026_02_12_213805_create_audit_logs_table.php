<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id')->nullable(); // Siapa yang melakukan
            $table->string('action'); // LOGIN, UPDATE_STATUS, DELETE_POST, CRAWL_START
            $table->string('target_table'); // Tabel apa yang dirubah
            $table->uuid('target_id'); // ID data yang dirubah
            $table->json('old_values')->nullable(); // Data sebelum dirubah
            $table->json('new_values')->nullable(); //Data setelah dirubah
            $table->string('ip_address'); // IP user
            $table->string('user_agent'); // Browser user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
