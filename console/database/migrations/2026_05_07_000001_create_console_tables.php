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
        // Projects table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'suspended', 'archived'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });

        // OAuth Applications table
        Schema::create('oauth_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret');
            $table->json('redirect_uris')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->timestamps();
            
            $table->index('project_id');
        });

        // API Keys table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('key')->unique();
            $table->string('name');
            $table->json('restrictions')->nullable();
            $table->integer('rate_limit')->default(1000);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('project_id');
        });

        // Play Store Apps table
        Schema::create('play_store_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('package_name')->unique();
            $table->string('app_name');
            $table->string('version');
            $table->enum('status', ['draft', 'review', 'published', 'rejected'])->default('draft');
            $table->json('metadata')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            
            $table->index('project_id');
        });

        // Subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('plan_id');
            $table->string('provider');
            $table->string('provider_subscription_id');
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled'])->default('trialing');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
        });

        // Billing Invoices table
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->json('line_items');
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
        });

        // AI Usage Logs table
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('model');
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->decimal('cost', 10, 6);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'created_at']);
        });

        // Webhook Endpoints table
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->json('events');
            $table->string('secret');
            $table->boolean('active')->default(true);
            $table->integer('max_retries')->default(3);
            $table->timestamps();
            
            $table->index('project_id');
        });

        // Webhook Deliveries table
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index(['webhook_endpoint_id', 'created_at']);
        });

        // Team Members table
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'developer', 'viewer'])->default('developer');
            $table->timestamps();
            
            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('play_store_apps');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('oauth_applications');
        Schema::dropIfExists('projects');
    }
};
