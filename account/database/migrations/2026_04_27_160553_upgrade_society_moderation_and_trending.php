<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add trending score to posts
        if (Schema::hasTable('society_posts')) {
            Schema::table('society_posts', function (Blueprint $table) {
                if (!Schema::hasColumn('society_posts', 'trending_score')) {
                    $table->decimal('trending_score', 12, 4)->default(0);
                }
                if (!Schema::hasColumn('society_posts', 'is_hidden')) {
                    $table->boolean('is_hidden')->default(false);
                }
            });
        }

        // Add society ban to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_society_banned')) {
                $table->boolean('is_society_banned')->default(false);
            }
        });
        
        // Reports Table
        if (!Schema::hasTable('society_reports')) {
            Schema::create('society_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->morphs('reportable');
                $table->string('reason');
                $table->text('details')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('society_reports');
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'is_society_banned')) {
                    $table->dropColumn('is_society_banned');
                }
            });
        }
        if (Schema::hasTable('society_posts')) {
            Schema::table('society_posts', function (Blueprint $table) {
                if (Schema::hasColumn('society_posts', 'trending_score')) {
                    $table->dropColumn('trending_score');
                }
                if (Schema::hasColumn('society_posts', 'is_hidden')) {
                    $table->dropColumn('is_hidden');
                }
            });
        }
    }
};
