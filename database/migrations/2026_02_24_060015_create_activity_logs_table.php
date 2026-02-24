<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // BLOCKCHAIN MECHANISM (Jantung Integritas Data)
            $table->string('previous_hash', 64)->nullable()->index();
            $table->string('current_hash', 64)->unique();

            // AKTOR (Siapa yang melakukan?)
            $table->uuid('user_id')->nullable()->index(); // Nullable jika sistem yang melakukan (cron)
            $table->string('user_ip', 45)->nullable();
            $table->string('user_agent')->nullable(); // Browser/Device apa yang dipakai

            // CONTEXT (Apa yang terjadi?)
            $table->string('event'); // created, updated, deleted, login, export
            $table->string('description'); // Penjelasan manusiawi
            
            // POLYMORPHIC (Objek apa yang diubah?)
            // Bisa nyambung ke User, CrawledData, atau model lain
            $table->uuid('subject_id')->nullable();
            $table->string('subject_type')->nullable(); 

            // FORENSIK DATA (Apa yang berubah?)
            // Menyimpan JSON: { "old": { "status": "pending" }, "new": { "status": "handled" } }
            $table->jsonb('properties')->nullable();

            $table->timestamps(); // Created_at adalah waktu kejadian
            
            // Catatan: Log TIDAK BOLEH di-soft delete atau di-update. 
            // Log bersifat "Append-Only" (Hanya boleh nambah).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
