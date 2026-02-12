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
        Schema::create('sentiment_analysis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('crawled_post_id')->constrained('crawled_posts')->onDelete('cascade');
            $table->enum('sentiment', ['positive', 'negative', 'neutral']);
            $table->float('confidence_score'); // 0.0 sampai 1.0 (Seberapa yakin AI-nya)
            $table->string('category'); // Kriminalitas, Laka Lantas, Pungli, dll
            $table->json('keywords'); // Simpan keyword penting: ["macet", "demo"]
            $table->enum('issue_status', ['open', 'investigating', 'resolved', 'ignored'])->default('open');
            $table->foreignUuid('handled_by')->nullable()->constrained('users'); // Siapa petugas yang menangani
            $table->text('police_notes')->nullable(); // Catatan petugas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentiment_analysis');
    }
};
