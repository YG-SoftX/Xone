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
        if (!Schema::hasTable('trending_searches')) {
            Schema::create('trending_searches', function (Blueprint $table) {
                $table->id();
                $table->string('query');
                $table->integer('count')->default(1);
                $table->date('trend_date');
                $table->timestamps();
                
                $table->unique(['query', 'trend_date']);
                $table->index('trend_date');
            });
        }

        if (!Schema::hasTable('search_training_data')) {
            Schema::create('search_training_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('query');
                $table->string('session_id')->nullable();
                $table->boolean('had_clicks')->default(false);
                $table->timestamps();
                
                $table->index('query');
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('search_result_rankings')) {
            Schema::create('search_result_rankings', function (Blueprint $table) {
                $table->id();
                $table->string('url');
                $table->string('result_type');
                $table->integer('click_count')->default(0);
                $table->integer('impression_count')->default(0);
                $table->decimal('ranking_score', 5, 2)->default(1.0);
                $table->timestamps();
                
                $table->unique(['url', 'result_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_result_rankings');
        Schema::dropIfExists('search_training_data');
        Schema::dropIfExists('trending_searches');
    }
};
