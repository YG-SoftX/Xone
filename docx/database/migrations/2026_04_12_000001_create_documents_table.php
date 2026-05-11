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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('set null');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->json('content_json')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('document_type')->default('document'); // document, spreadsheet, presentation
            $table->string('status')->default('draft'); // draft, published, archived
            $table->integer('word_count')->default(0);
            $table->integer('page_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['folder_id', 'created_at']);
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
