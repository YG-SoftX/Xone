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
        // 🛡️ Ensure the base API Products table exists first
        if (!Schema::hasTable('api_products')) {
            Schema::create('api_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('display_name')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('icon_url')->nullable();
                $table->string('api_base_url')->nullable();
                $table->string('base_url')->nullable();
                $table->string('documentation_url')->nullable();
                $table->string('category')->nullable();
                $table->boolean('requires_approval')->default(false);
                $table->string('pricing_model')->nullable();
                $table->json('features')->nullable();
                $table->json('endpoints')->nullable();
                $table->decimal('base_price', 10, 2)->default(0);
                $table->decimal('price_per_1000_calls', 10, 4)->default(0);
                $table->integer('default_daily_quota')->nullable();
                $table->integer('default_monthly_quota')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ─── Developer Projects (like GCP projects) ───
        if (!Schema::hasTable('developer_projects')) {
            Schema::create('developer_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('project_id')->unique();    // e.g., "ygxone-myapp-1234"
                $table->text('description')->nullable();
                $table->string('website_url')->nullable();
                $table->enum('environment', ['development', 'staging', 'production'])->default('development');
                $table->boolean('is_active')->default(true);
                $table->json('labels')->nullable();         // key-value tags
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();

                $table->index(['owner_id', 'is_active']);
                $table->index('project_id');
            });
        }

        // ─── Project Members (team access) ───
        if (!Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('role', ['owner', 'editor', 'viewer', 'billing_admin'])->default('viewer');
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
            });
        }

        // ─── API Credentials (all types: API keys, OAuth clients, service accounts) ───
        if (!Schema::hasTable('api_credentials')) {
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->enum('type', ['api_key', 'oauth_client', 'service_account', 'webhook_secret']);
                $table->string('name');
                $table->string('identifier')->unique();     // public-facing ID (e.g., yg_api_abc123)
                $table->string('secret');                   // hashed secret (never shown after creation)
                $table->json('scopes')->nullable();         // allowed permissions
                $table->json('restrictions')->nullable();   // IP, referrer, app restrictions
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->bigInteger('total_requests')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('created_by_ip')->nullable();
                $table->string('created_by_ua')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'type']);
                $table->index('identifier');
            });
        }

        // ─── Quotas (per project per product) ───
        if (!Schema::hasTable('project_quotas')) {
            Schema::create('project_quotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained('api_products')->nullOnDelete();
                $table->integer('daily_limit')->default(10000);
                $table->integer('monthly_limit')->default(300000);
                $table->integer('rate_limit_per_minute')->default(60);
                $table->integer('daily_used')->default(0);
                $table->integer('monthly_used')->default(0);
                $table->date('daily_reset_date')->nullable();
                $table->date('monthly_reset_date')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'product_id']);
            });
        }

        // ─── API Usage Logs ───
        if (!Schema::hasTable('api_usage_logs')) {
            Schema::create('api_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credential_id')->constrained('api_credentials')->onDelete('cascade');
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained('api_products')->nullOnDelete();
                $table->string('endpoint');
                $table->string('method')->default('GET');
                $table->integer('status_code')->nullable();
                $table->integer('response_time_ms')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->json('request_metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['project_id', 'created_at']);
                $table->index(['credential_id', 'created_at']);
                $table->index('created_at');
            });
        }

        // ─── Webhooks ───
        if (!Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->string('name');
                $table->string('url');
                $table->string('secret');
                $table->json('events');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_triggered_at')->nullable();
                $table->integer('success_count')->default(0);
                $table->integer('failure_count')->default(0);
                $table->timestamps();

                $table->index(['project_id', 'is_active']);
            });
        }

        // ─── Webhook Deliveries ───
        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
                $table->string('event_type');
                $table->json('payload');
                $table->integer('status_code')->nullable();
                $table->text('response_body')->nullable();
                $table->integer('attempt')->default(1);
                $table->boolean('success')->default(false);
                $table->timestamp('next_retry_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['webhook_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('project_quotas');
        Schema::dropIfExists('api_credentials');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('developer_projects');
        Schema::dropIfExists('api_products');
    }
};
