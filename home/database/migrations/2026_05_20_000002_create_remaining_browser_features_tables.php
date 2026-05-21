<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for remaining browser features:
     * - Password manager storage
     * - Follow-up suggestion tracking
     * - Visual summary cache
     */
    public function up(): void
    {
        // Saved Passwords Table
        Schema::create('saved_passwords', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->index(); // e.g., "github.com"
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->text('username'); // Encrypted
            $table->text('password'); // Encrypted
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->unique(['domain', 'user_id'], 'domain_user_unique');
            $table->unique(['domain', 'session_id'], 'domain_session_unique');
            $table->index(['last_used_at']);
        });
        
        // Follow-up Suggestions Feedback Table (for ML training)
        Schema::create('suggestion_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('task_id')->index(); // Original task/research ID
            $table->integer('selected_index'); // Which suggestion was clicked (0-based)
            $table->boolean('helpful'); // User feedback
            $table->json('context')->nullable(); // Task context at time of suggestion
            $table->timestamps();
            
            $table->index(['task_id', 'created_at']);
            $table->index(['helpful', 'created_at']);
        });
        
        // Visual Summary Cache Table
        Schema::create('visual_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('url_hash')->index(); // MD5 hash of URL for quick lookup
            $table->text('url'); // Full URL
            $table->json('summary_data'); // Cached visualizations
            $table->integer('chart_count')->default(0);
            $table->integer('table_count')->default(0);
            $table->timestamp('cached_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            
            $table->index(['expires_at']);
        });
        
        // Developer Tools Session Log
        Schema::create('dev_tools_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('log_type')->index(); // network, console, elements, performance
            $table->json('log_data');
            $table->timestamp('logged_at')->index();
            $table->timestamps();
            
            $table->index(['session_id', 'log_type', 'logged_at']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_tools_logs');
        Schema::dropIfExists('visual_summaries');
        Schema::dropIfExists('suggestion_feedback');
        Schema::dropIfExists('saved_passwords');
    }
};
