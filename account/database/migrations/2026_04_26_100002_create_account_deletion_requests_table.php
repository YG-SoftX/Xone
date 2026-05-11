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
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('requested_at');
            $table->timestamp('scheduled_deletion_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending')->index(); // pending, cancelled, completed
            $table->string('ip_address')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'scheduled_deletion_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
