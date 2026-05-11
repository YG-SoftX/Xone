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
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'laravel-scheduler', 'queue-worker'
            $table->text('command'); // The actual cron command
            $table->string('schedule'); // Cron expression: * * * * *
            $table->string('description')->nullable(); // Human-readable description
            $table->boolean('is_enabled')->default(true); // Enable/disable toggle
            $table->boolean('is_system')->default(false); // System jobs can't be deleted
            $table->timestamp('last_run_at')->nullable(); // Last execution time
            $table->timestamp('next_run_at')->nullable(); // Next scheduled run
            $table->integer('total_runs')->default(0); // Total executions count
            $table->integer('failed_runs')->default(0); // Failed executions count
            $table->text('last_output')->nullable(); // Last execution output/error
            $table->string('status')->default('pending'); // pending, running, success, failed
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();
            
            $table->index(['is_enabled', 'last_run_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
