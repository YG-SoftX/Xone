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
        if (!Schema::hasTable('third_party_apps')) {
            Schema::create('third_party_apps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
                $table->string('name'); // App display name
                $table->string('slug')->unique(); // URL-safe identifier
                $table->string('description')->nullable();
                $table->string('website_url')->nullable();
                $table->string('redirect_uri'); // SSO callback URL
                $table->string('client_id')->unique(); // OAuth-like client identifier
                $table->string('client_secret'); // Hashed secret
                $table->json('scopes')->nullable(); // Allowed permissions
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->bigInteger('total_auth_requests')->default(0);
                $table->bigInteger('total_api_calls')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index('client_id');
            });
        }

        if (!Schema::hasTable('third_party_auth_logs')) {
            Schema::create('third_party_auth_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->constrained('third_party_apps')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // End user who authenticated
                $table->string('event_type'); // 'sso_initiated', 'sso_validated', 'token_created', 'token_revoked'
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->json('metadata')->nullable(); // Extra context
                $table->timestamp('created_at')->useCurrent();

                $table->index(['app_id', 'event_type']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_auth_logs');
        Schema::dropIfExists('third_party_apps');
    }
};
