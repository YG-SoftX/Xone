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
        if (!Schema::hasTable('search_history')) {
            Schema::create('search_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('query');
                $table->integer('result_count')->default(0);
                $table->json('filters')->nullable();
                $table->timestamp('created_at');
                
                $table->index(['user_id', 'created_at']);
                $table->index('query');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
