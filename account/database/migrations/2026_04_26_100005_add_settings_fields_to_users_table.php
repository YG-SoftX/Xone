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
        Schema::table('users', function (Blueprint $table) {
            // Two-Factor Authentication
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable();
            }
            
            // Privacy Settings
            if (!Schema::hasColumn('users', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable();
            }
            
            // Notification Preferences
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_settings')) {
                $table->json('email_settings')->nullable();
            }
            if (!Schema::hasColumn('users', 'subscribed_to_newsletter')) {
                $table->boolean('subscribed_to_newsletter')->default(false);
            }
            
            // Account Deletion
            if (!Schema::hasColumn('users', 'deletion_scheduled_at')) {
                $table->timestamp('deletion_scheduled_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'deletion_requested_ip')) {
                $table->string('deletion_requested_ip')->nullable();
            }
            
            // Phone for SMS notifications
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'privacy_settings',
                'notification_preferences',
                'email_settings',
                'subscribed_to_newsletter',
                'deletion_scheduled_at',
                'deletion_requested_ip',
                'phone',
                'phone_verified_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
