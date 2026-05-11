<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posts Table
        Schema::create('society_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable();
            $table->string('media_type')->nullable(); // image, video, file
            $table->string('media_url')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        // Comments Table
        Schema::create('society_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained('society_posts')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('society_comments')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });

        // Likes Table
        Schema::create('society_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained('society_posts')->onDelete('cascade');
            $table->unique(['user_id', 'post_id']);
            $table->timestamps();
        });

        // Follows/Connections Table
        Schema::create('society_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->unique(['follower_id', 'following_id']);
            $table->timestamps();
        });

        // Blocks Table
        Schema::create('society_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('blocked_id')->constrained('users')->onDelete('cascade');
            $table->unique(['user_id', 'blocked_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('society_blocks');
        Schema::dropIfExists('society_follows');
        Schema::dropIfExists('society_likes');
        Schema::dropIfExists('society_comments');
        Schema::dropIfExists('society_posts');
    }
};
