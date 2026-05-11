<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Forms module
     */
    public function up(): void
    {
        // Forms
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('uuid')->unique(); // Public access URL
            $table->boolean('is_published')->default(false);
            $table->boolean('accept_responses')->default(true);
            $table->integer('max_responses')->nullable(); // null = unlimited
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->boolean('require_login')->default(false);
            $table->boolean('allow_multiple_submissions')->default(true);
            $table->boolean('show_progress_bar')->default(true);
            $table->boolean('shuffle_questions')->default(false);
            $table->json('theme')->nullable(); // Colors, fonts, header image
            $table->json('confirmation_message')->nullable(); // Custom thank you message
            $table->integer('response_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'is_published']);
            $table->index('uuid');
        });

        // Form questions
        Schema::create('form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                'short_text',
                'paragraph',
                'multiple_choice',
                'checkboxes',
                'dropdown',
                'linear_scale',
                'date',
                'time',
                'file_upload',
                'email',
                'number',
                'section'
            ])->default('short_text');
            $table->boolean('required')->default(false);
            $table->json('options')->nullable(); // For multiple choice, checkboxes, dropdown
            $table->json('validation')->nullable(); // Min/max length, regex patterns
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('parent_section_id')->nullable();
            $table->boolean('has_logic')->default(false);
            $table->json('logic_rules')->nullable(); // Conditional branching
            $table->timestamps();
            
            $table->index(['form_id', 'order']);
        });

        // Form responses
        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('respondent_email')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('answers'); // Key-value pairs of question_id => answer
            $table->boolean('is_complete')->default(true);
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            $table->index(['form_id', 'submitted_at']);
            $table->index('respondent_email');
        });

        // Form collaborators
        Schema::create('form_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'editor', 'viewer'])->default('viewer');
            $table->timestamps();
            
            $table->unique(['form_id', 'user_id']);
        });

        // Form templates
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // survey, quiz, registration, feedback, etc.
            $table->json('questions'); // Pre-built question structure
            $table->json('theme')->nullable();
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('category');
        });

        // Form analytics
        Schema::create('form_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('starts')->default(0);
            $table->integer('completions')->default(0);
            $table->float('completion_rate')->default(0);
            $table->float('avg_time_seconds')->nullable();
            $table->json('device_breakdown')->nullable(); // mobile/desktop/tablet
            $table->json('referrer_stats')->nullable();
            $table->timestamps();
            
            $table->unique(['form_id', 'date']);
        });

        // File uploads from forms
        Schema::create('form_file_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('form_responses')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('form_questions')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->string('storage_path');
            $table->timestamps();
            
            $table->index('response_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_file_uploads');
        Schema::dropIfExists('form_analytics');
        Schema::dropIfExists('form_templates');
        Schema::dropIfExists('form_collaborators');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_questions');
        Schema::dropIfExists('forms');
    }
};
