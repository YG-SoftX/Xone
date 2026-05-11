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
        Schema::table('mails', function (Blueprint $table) {
            // Email categorization (Primary/Social/Promotions/Updates)
            $table->string('category')->nullable()->after('folder')
                  ->comment('primary, social, promotions, updates');
            
            // Sentiment analysis
            $table->decimal('sentiment_score', 3, 2)->nullable()->after('category')
                  ->comment('Sentiment score: -1 (negative) to 1 (positive)');
            $table->string('sentiment_label')->nullable()->after('sentiment_score')
                  ->comment('negative, neutral, positive');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->after('sentiment_label')
                  ->comment('Confidence score: 0 to 1');
            
            // Priority flagging based on sentiment
            $table->string('priority')->default('normal')->after('sentiment_confidence')
                  ->comment('low, normal, high');
            
            // Indexes for performance
            $table->index('category');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['priority']);
            $table->dropColumn([
                'category',
                'sentiment_score',
                'sentiment_label',
                'sentiment_confidence',
                'priority'
            ]);
        });
    }
};
