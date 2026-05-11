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
        // Search training data (for AI learning)
        Schema::create('search_training_data', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->integer('result_count')->default(0);
            $table->boolean('had_clicks')->default(false);
            $table->string('session_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            
            $table->index(['query', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
        
        // User search patterns (personalization)
        Schema::create('user_search_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query_pattern'); // Normalized pattern (e.g., "how to *" or "* tutorial")
            $table->integer('frequency')->default(1);
            $table->timestamp('last_searched');
            $table->timestamps();
            
            $table->unique(['user_id', 'query_pattern']);
        });
        
        // Search result rankings (click-through feedback)
        Schema::create('search_result_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('result_type'); // web, mail, drive, docs, contacts
            $table->decimal('ranking_score', 5, 2)->default(1.0);
            $table->integer('click_count')->default(0);
            $table->integer('impression_count')->default(0);
            $table->timestamps();
            
            $table->unique(['url', 'result_type']);
        });
        
        // Trending searches
        Schema::create('trending_searches', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->integer('count')->default(1);
            $table->timestamp('trend_date');
            $table->timestamps();
            
            $table->index(['trend_date', 'count']);
        });
        
        // Web pages index
        Schema::create('web_pages', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('snippet')->nullable();
            $table->string('domain');
            $table->timestamp('last_crawled_at')->nullable();
            $table->float('page_rank')->default(1.0);
            $table->integer('visit_count')->default(0);
            $table->timestamps();
            
            $table->index(['domain', 'page_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_pages');
        Schema::dropIfExists('trending_searches');
        Schema::dropIfExists('search_result_rankings');
        Schema::dropIfExists('user_search_patterns');
        Schema::dropIfExists('search_training_data');
    }
};
