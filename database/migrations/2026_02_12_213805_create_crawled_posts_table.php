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
        Schema::create('crawled_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform')->default('X.com');
            $table->string('external_id'); //ID asli dari X untuk cegah duplikat
            $table->string('username');
            $table->text('content');
            $table->string('post_url');
            $table->timestamp('posted_at');
            $table->enum('type', ['post', 'reply', 'comment']);
            $table->uuid('parent_id')->nullable(); // Jika ini reply, induknya siapa?
            $table->string('crawl_status')->default('pending'); // pending, processed (sudah di AI)
            $table->timestamps();

            // Indexing biar pencarian cepat
            $table->index(['created_at', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawled_posts');
    }
};
