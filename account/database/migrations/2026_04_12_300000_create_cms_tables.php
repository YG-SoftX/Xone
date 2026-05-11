<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Theme settings - stores colors, fonts, logos for each service
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('service')->default('account'); // account, mail, docx, xcel
            $table->string('name'); // default, dark_mode, custom
            $table->boolean('is_active')->default(false);
            $table->json('colors')->nullable(); // {primary, secondary, background, surface, text, accent}
            $table->json('fonts')->nullable(); // {heading, body, sizes}
            $table->json('logos')->nullable(); // {favicon, header_logo, header_logo_dark, app_icon}
            $table->json('settings')->nullable(); // {border_radius, shadow_style, spacing}
            $table->timestamps();
        });

        // Frontend content - terms, privacy, about, etc.
        Schema::create('frontend_content', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // terms_of_service, privacy_policy, about_us, help_center
            $table->string('title');
            $table->longText('content'); // HTML content
            $table->string('version')->default('1.0');
            $table->string('locale')->default('en');
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Developer documentation
        Schema::create('documentation', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // api, sdk, guides, reference
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content'); // Markdown or HTML
            $table->integer('order')->default(0);
            $table->string('parent_id')->nullable(); // for nested docs
            $table->json('metadata')->nullable(); // {version, tags, code_examples}
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Articles/Blog posts
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('category')->default('general'); // news, tutorial, announcement, blog
            $table->json('tags')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('views')->default(0);
            $table->timestamps();
        });

        // Support tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable(); // for non-logged-in users
            $table->string('name')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->string('category')->default('general'); // billing, technical, account, feature_request, bug
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->string('service')->nullable(); // account, mail, docx, xcel
            $table->json('attachments')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Support ticket replies
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_staff')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('documentation');
        Schema::dropIfExists('frontend_content');
        Schema::dropIfExists('themes');
    }
};
