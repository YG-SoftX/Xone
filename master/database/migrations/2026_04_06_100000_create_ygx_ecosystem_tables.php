<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('domain')->unique()->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('plan_name');
                $table->string('status')->default('active');
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('app_modules')) {
            Schema::create('app_modules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('icon')->default('heroicon-o-cube');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_core')->default(false);
                $table->string('base_url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('developers')) {
            Schema::create('developers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('company')->nullable();
                $table->string('status')->default('pending');
                $table->string('api_key')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('third_party_apps')) {
            Schema::create('third_party_apps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('developer_id')->constrained('developers')->cascadeOnDelete();
                $table->string('name');
                $table->string('version')->default('1.0.0');
                $table->string('status')->default('under_review');
                $table->text('description')->nullable();
                $table->decimal('price', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('infrastructure_nodes')) {
            Schema::create('infrastructure_nodes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('ip_address')->nullable();
                $table->string('region')->default('us-east');
                $table->string('type')->default('app_server'); // database, cache, app_server, worker
                $table->string('status')->default('running');
                $table->decimal('cpu_usage', 5, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_nodes');
        Schema::dropIfExists('third_party_apps');
        Schema::dropIfExists('developers');
        Schema::dropIfExists('app_modules');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('tenants');
    }
};
