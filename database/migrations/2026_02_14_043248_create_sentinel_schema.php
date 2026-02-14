<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel User Petugas (Secure Identity)
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('nrp')->unique(); // Login ID
            $table->string('password');
            $table->enum('role', ['officer', 'analyst', 'superadmin']);
            $table->string('region_code')->index(); // Kode Wilayah (misal: JATENG-SMG)
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Audit trail: Data tidak benar-benar hilang
        });

        // 2. Tabel Penulis/Akun Medsos (Author Normalization)
        Schema::create('social_authors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform_user_id')->index(); // ID asli Twitter/FB
            $table->string('username');
            $table->string('platform')->default('twitter');
            $table->integer('trust_score')->default(50); // Skor kredibilitas akun (bot detection)
            $table->timestamps();
            
            // Mencegah duplikasi author dari platform yang sama
            $table->unique(['platform', 'platform_user_id']);
        });

        // 3. Tabel Postingan (Raw Data)
        Schema::create('social_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->string('social_post_id')->unique(); // ID unik postingan dari platform
            $table->text('content'); // Isi mentah
            $table->string('url_permalink');
            $table->dateTime('posted_at');
            $table->enum('type', ['post', 'reply', 'quote']);
            $table->uuid('parent_post_id')->nullable(); // Self-referencing untuk reply
            $table->jsonb('raw_metadata')->nullable(); // Simpan raw JSON crawl jaga-jaga
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('social_authors');
            $table->foreign('parent_post_id')->references('id')->on('social_posts');
        });

        // 4. Tabel Hasil Analisis AI (Intelligence)
        Schema::create('social_analytics', function (Blueprint $table) {
            $table->uuid('post_id')->primary(); // 1 Post = 1 Analisis
            $table->enum('sentiment', ['positive', 'neutral', 'negative']);
            $table->float('confidence_score'); // 0.0 - 1.0
            $table->string('main_topic')->index(); // Kriminal, Lantas, Pungli
            $table->jsonb('keywords'); // Word cloud data
            $table->timestamp('analyzed_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('social_posts')->onDelete('cascade');
        });

        // 5. Tabel Operasional Polisi (Case Management)
        Schema::create('case_operations', function (Blueprint $table) {
            $table->uuid('post_id')->primary();
            $table->enum('status', ['open', 'monitoring', 'handled', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->uuid('assigned_officer_id')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreign('assigned_officer_id')->references('id')->on('users');
        });

        // 6. Audit Logs (Immutable)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('user_id')->nullable();
            $table->string('action'); // LOGIN, EXPORT, CRAWL
            $table->string('target'); // Table/Resource
            $table->ipAddress('ip_address');
            $table->text('user_agent');
            $table->jsonb('payload')->nullable(); // Data yang dikirim
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('case_operations');
        Schema::dropIfExists('social_analytics');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_authors');
        Schema::dropIfExists('users');
    }
};
