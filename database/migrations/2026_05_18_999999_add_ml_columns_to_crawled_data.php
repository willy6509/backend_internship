<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('crawled_data', function (Blueprint $table) {
            if (!Schema::hasColumn('crawled_data', 'ai_sentiment')) {
                $table->string('ai_sentiment')->nullable();
                $table->string('main_topic')->nullable();
                $table->boolean('is_emergency')->default(false);
                $table->string('location')->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('crawled_data', function (Blueprint $table) {
            $table->dropColumn(['ai_sentiment', 'main_topic', 'is_emergency', 'location']);
        });
    }
};
