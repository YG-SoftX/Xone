<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexed_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_id'); // ID in the original module
            $table->string('service');   // mail, drive, docs, excel, contacts, calendar, chat
            $table->string('title');
            $table->text('content')->nullable(); // For full-text search
            $table->text('snippet')->nullable();
            $table->string('url')->nullable();     // Deep link to the item
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('metadata')->nullable();  // Service-specific data (size, date, etc.)
            $table->float('relevance_boost')->default(1.0);
            $table->timestamps();

            $table->index(['service', 'source_id']);
            $table->index(['user_id', 'service']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('indexed_items');
    }
};
