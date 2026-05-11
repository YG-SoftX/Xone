<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG AI module (Intelligence Layer)
     */
    public function up(): void
    {
        // AI queries
        if (!Schema::hasTable('ai_queries')) {
            Schema::create('ai_queries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service'); // mail, drive, docs, meet, pay, general
                $table->text('query');
                $table->longText('response')->nullable();
                $table->string('model_used')->nullable(); // gpt-4, claude, etc.
                $table->integer('tokens_used')->nullable();
                $table->decimal('cost', 10, 4)->nullable();
                $table->float('confidence_score')->nullable();
                $table->json('metadata')->nullable(); // Context, suggestions, etc.
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'created_at']);
                $table->index('service');
            });
        }

        // AI models
        if (!Schema::hasTable('ai_models')) {
            Schema::create('ai_models', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // GPT-4, Claude, Gemini
                $table->string('provider'); // OpenAI, Anthropic, Google
                $table->text('description')->nullable();
                $table->json('capabilities')->nullable(); // text, image, code, etc.
                $table->decimal('cost_per_token', 10, 6)->default(0.00);
                $table->integer('max_tokens')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // AI suggestions
        if (!Schema::hasTable('ai_suggestions')) {
            Schema::create('ai_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service'); // mail, drive, docs
                $table->string('suggestion_type'); // smart_reply, categorization, summary
                $table->unsignedBigInteger('item_id')->nullable(); // email_id, file_id, etc.
                $table->string('item_type')->nullable();
                $table->json('suggestion_data'); // The actual suggestion
                $table->boolean('is_accepted')->default(false);
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'service']);
            });
        }

        // Email categorization (AI-powered)
        if (!Schema::hasTable('ai_email_categories')) {
            Schema::create('ai_email_categories', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('mail_messages')) {
                    $table->foreignId('message_id')->constrained('mail_messages')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('message_id');
                }
                $table->enum('category', ['primary', 'social', 'promotions', 'updates', 'forums'])->default('primary');
                $table->float('confidence_score');
                $table->json('reasoning')->nullable();
                $table->timestamps();
            });
        }

        // Document summaries
        if (!Schema::hasTable('ai_document_summaries')) {
            Schema::create('ai_document_summaries', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('docs_documents')) {
                    $table->foreignId('document_id')->constrained('docs_documents')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('document_id');
                }
                $table->text('summary');
                $table->json('key_points')->nullable();
                $table->string('language')->default('en');
                $table->integer('word_count')->nullable();
                $table->timestamps();
            });
        }

        // Image recognition results
        if (!Schema::hasTable('ai_image_recognition')) {
            Schema::create('ai_image_recognition', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('drive_files')) {
                    $table->foreignId('file_id')->constrained('drive_files')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('file_id');
                }
                $table->json('labels');
                $table->json('colors')->nullable();
                $table->string('ocr_text')->nullable();
                $table->json('faces')->nullable();
                $table->float('confidence_score');
                $table->timestamps();
            });
        }

        // Meeting transcripts
        if (!Schema::hasTable('ai_meeting_transcripts')) {
            Schema::create('ai_meeting_transcripts', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('meet_meetings')) {
                    $table->foreignId('meeting_id')->constrained('meet_meetings')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('meeting_id');
                }
                $table->longText('transcript');
                $table->text('summary')->nullable();
                $table->json('action_items')->nullable();
                $table->json('speakers')->nullable();
                $table->integer('duration_seconds');
                $table->string('language')->default('en');
                $table->timestamps();
            });
        }

        // Fraud detection scores
        if (!Schema::hasTable('ai_fraud_scores')) {
            Schema::create('ai_fraud_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                if (Schema::hasTable('pay_transactions')) {
                    $table->foreignId('transaction_id')->nullable()->constrained('pay_transactions')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('transaction_id')->nullable();
                }
                $table->float('fraud_score');
                $table->json('risk_factors');
                $table->enum('decision', ['approve', 'review', 'block'])->default('approve');
                $table->text('explanation')->nullable();
                $table->timestamps();
            });
        }

        // Training data
        if (!Schema::hasTable('ai_training_data')) {
            Schema::create('ai_training_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('dataset_name');
                $table->string('data_type'); // text, image, structured
                $table->json('data');
                $table->json('labels')->nullable();
                $table->boolean('is_processed')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_data');
        Schema::dropIfExists('ai_fraud_scores');
        Schema::dropIfExists('ai_meeting_transcripts');
        Schema::dropIfExists('ai_image_recognition');
        Schema::dropIfExists('ai_document_summaries');
        Schema::dropIfExists('ai_email_categories');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_queries');
    }
};
