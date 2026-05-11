<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('society_rl_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('society_posts')->onDelete('cascade');
            $table->decimal('initial_safety_score', 5, 2)->nullable();
            $table->string('initial_verdict')->nullable();
            
            // The RL Signal
            $table->decimal('reward_signal', 3, 2)->default(0); // -1.0 to 1.0
            $table->string('feedback_type'); // positive_engagement, community_report, admin_reversal
            
            $table->boolean('is_processed')->default(false); // Flag for training export
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('society_rl_feedback');
    }
};
