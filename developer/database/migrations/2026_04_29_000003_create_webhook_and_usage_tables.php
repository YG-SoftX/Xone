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
        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
                $table->integer('status_code')->nullable();
                $table->text('response_body')->nullable();
                $table->decimal('response_time_ms', 10, 2)->nullable();
                $table->json('request_payload')->nullable();
                $table->string('signature')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->boolean('is_success')->default(false);
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['webhook_id', 'delivered_at']);
                $table->index('is_success');
            });
        }

        if (!Schema::hasTable('api_usage')) {
            Schema::create('api_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('api_key_id')->constrained('api_credentials')->onDelete('cascade');
                $table->foreignId('project_id')->constrained('developer_projects')->onDelete('cascade');
                $table->string('endpoint');
                $table->string('method', 10);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->decimal('response_time_ms', 10, 2)->nullable();
                $table->integer('status_code')->nullable();
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['project_id', 'created_at']);
                $table->index(['api_key_id', 'created_at']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('api_usage');
    }
};
