<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure support module tables exist so the Filament admin panel
     * can manage support tickets and knowledge base articles.
     *
     * These tables are also created by the support module's own migrations,
     * but this migration ensures they exist even if the support module
     * hasn't been deployed to the same database yet.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('subject');
                $table->string('category')->default('other');
                $table->string('priority')->default('medium');
                $table->string('status')->default('open');
                $table->json('messages')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content');
                $table->string('category')->nullable();
                $table->enum('type', ['article', 'faq'])->default('article');
                $table->boolean('published')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Don't drop tables — other modules may rely on them
    }
};
