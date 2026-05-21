<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations for remaining browser features (Option A - Complete):
     * - Extensions marketplace and management
     * - Developer tools logging
     * - Offline mode cache tracking
     * - Cross-device sync tables
     * - Source comparison history
     */
    public function up(): void
    {
        // Browser Extensions Table
        Schema::create('browser_extensions', function (Blueprint $table) {
            $table->id();
            $table->string('extension_id')->unique()->index();
            $table->string('name');
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('author')->default('Unknown');
            $table->json('permissions')->nullable();
            $table->json('content_scripts')->nullable();
            $table->json('background_scripts')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('installed_at');
            $table->timestamps();
            
            $table->index(['is_enabled', 'installed_at']);
        });
        
        // Synced Bookmarks Table
        Schema::create('synced_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('url');
            $table->string('title')->nullable();
            $table->string('folder')->default('Unsorted');
            $table->timestamps();
            
            $table->unique(['user_id', 'url'], 'user_url_unique');
            $table->index(['user_id', 'folder']);
        });
        
        // Synced History Table
        Schema::create('synced_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('url');
            $table->string('title')->nullable();
            $table->timestamp('visited_at');
            $table->string('device_id')->index();
            $table->timestamps();
            
            $table->index(['user_id', 'visited_at']);
            $table->index(['user_id', 'device_id']);
        });
        
        // Synced Settings Table
        Schema::create('synced_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();
            $table->json('settings_data');
            $table->timestamps();
        });
        
        // Synced Devices Table
        Schema::create('synced_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('device_id')->index();
            $table->string('device_name');
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('pending_sync_type')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'device_id'], 'user_device_unique');
            $table->index(['user_id', 'is_active']);
        });
        
        // Offline Cache Index Table (for tracking cached pages)
        Schema::create('offline_cache_index', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique()->index(); // MD5 of URL
            $table->text('url');
            $table->string('title')->nullable();
            $table->integer('size_bytes')->default(0);
            $table->timestamp('cached_at');
            $table->timestamp('expires_at')->index();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            $table->index(['expires_at', 'is_valid']);
        });
        
        // Source Comparison History Table
        Schema::create('source_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('source_1_url');
            $table->text('source_2_url');
            $table->float('similarity_score');
            $table->json('comparison_data');
            $table->timestamp('compared_at')->index();
            $table->timestamps();
            
            $table->index(['session_id', 'compared_at']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_comparisons');
        Schema::dropIfExists('offline_cache_index');
        Schema::dropIfExists('synced_devices');
        Schema::dropIfExists('synced_settings');
        Schema::dropIfExists('synced_history');
        Schema::dropIfExists('synced_bookmarks');
        Schema::dropIfExists('browser_extensions');
    }
};
