<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for advanced browser features:
     * - Deep research citations tracking
     * - Knowledge graph entities and relationships
     * - Agent session history
     */
    public function up(): void
    {
        // Browser Citations Table
        Schema::create('browser_citations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('url');
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->date('published_date')->nullable();
            $table->string('site_name')->nullable();
            $table->string('context')->default('browsing'); // browsing, research, agent
            $table->integer('visit_count')->default(1);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
        
        // Knowledge Graph - Entities Table
        Schema::create('knowledge_entities', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name')->index();
            $table->string('type')->index(); // person, organization, location, date, concept
            $table->float('confidence')->default(0.5);
            $table->integer('mentions')->default(1);
            $table->string('first_seen_url')->nullable();
            $table->json('related_urls')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            
            $table->unique(['session_id', 'name'], 'session_entity_unique');
            $table->index(['type', 'mentions']);
        });
        
        // Knowledge Graph - Relationships Table
        Schema::create('knowledge_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_entity_id')->index();
            $table->unsignedBigInteger('target_entity_id')->index();
            $table->string('type')->default('co_occurrence'); // co_occurrence, mentions, related_to
            $table->integer('strength')->default(1);
            $table->integer('co_occurrence_count')->default(1);
            $table->string('context_url')->nullable();
            $table->timestamps();
            
            $table->foreign('source_entity_id')->references('id')->on('knowledge_entities')->onDelete('cascade');
            $table->foreign('target_entity_id')->references('id')->on('knowledge_entities')->onDelete('cascade');
            $table->unique(['source_entity_id', 'target_entity_id'], 'relationship_unique');
        });
        
        // Agent Session History Table
        Schema::create('agent_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('task_description');
            $table->json('steps_log')->nullable();
            $table->text('final_response')->nullable();
            $table->boolean('success')->default(false);
            $table->integer('tool_calls_count')->default(0);
            $table->float('elapsed_seconds')->nullable();
            $table->string('llm_backend')->nullable(); // openai, claude, ollama
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['success', 'created_at']);
        });
        
        // Research Reports Table
        Schema::create('research_reports', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('query');
            $table->json('report_data')->nullable();
            $table->integer('sources_count')->default(0);
            $table->float('elapsed_seconds')->nullable();
            $table->string('format')->default('markdown'); // markdown, html, json
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->fullText('query');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_reports');
        Schema::dropIfExists('agent_sessions');
        Schema::dropIfExists('knowledge_relationships');
        Schema::dropIfExists('knowledge_entities');
        Schema::dropIfExists('browser_citations');
    }
};
