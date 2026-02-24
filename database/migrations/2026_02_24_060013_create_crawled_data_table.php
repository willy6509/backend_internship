<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawled_data', function (Blueprint $table) {
            // Menggunakan UUID untuk keamanan ID yang lebih baik di sistem terdistribusi
            $table->uuid('id')->primary();
            
            // Konsep Blockchain: Mengikat data sebelumnya dan data saat ini
            $table->string('previous_hash', 64)->nullable()->index(); // Kosong hanya untuk Genesis Block (data pertama)
            $table->string('current_hash', 64)->unique();
            
            // Meta Data Crawling
            $table->enum('type', ['post', 'reply'])->index();
            $table->string('source')->default('X');
            $table->string('username')->index();
            $table->timestamp('posted_at');
            $table->text('content');
            $table->string('url')->unique();
            $table->string('parent_url')->nullable()->index();
            
            // Forensik: Menyimpan JSON mentah asli dari sumber
            $table->jsonb('raw_payload')->nullable();
            
            // Waktu sistem mencatat (Timestamp & Soft Deletes wajib untuk audit)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawled_data');
    }
};
