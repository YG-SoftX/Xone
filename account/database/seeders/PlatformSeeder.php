<?php

namespace Database\Seeders;

use App\Models\PlatformFeature;
use App\Models\YgService;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Default Platform features
        $features = [
            ['feature_key' => 'wallet', 'feature_name' => 'Wallet & Payments', 'description' => 'User wallet, transactions, and payment features', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'nfc', 'feature_name' => 'NFC Payments', 'description' => 'NFC tap-to-pay functionality', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'kyc', 'feature_name' => 'Identity Verification', 'description' => 'KYC document upload and verification', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'password_manager', 'feature_name' => 'Password Manager', 'description' => 'Encrypted password vault', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'two_factor', 'feature_name' => 'Two-Factor Authentication', 'description' => '2FA with Google Authenticator', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'activity_log', 'feature_name' => 'Activity Log', 'description' => 'User activity tracking', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'invoices', 'feature_name' => 'Invoicing', 'description' => 'Create and manage invoices', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'sharing', 'feature_name' => 'People & Sharing', 'description' => 'Social sharing features', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'privacy', 'feature_name' => 'Data & Privacy', 'description' => 'Privacy controls and data export', 'is_enabled' => true, 'is_public' => true],
            ['feature_key' => 'subscriptions', 'feature_name' => 'Subscriptions', 'description' => 'Recurring subscription plans', 'is_enabled' => true, 'is_public' => true],
        ];

        foreach ($features as $feature) {
            PlatformFeature::updateOrCreate(
                ['feature_key' => $feature['feature_key']],
                $feature
            );
        }

        // Default YG Services
        $services = [
            ['service_key' => 'mail', 'service_name' => 'YG Mail', 'url' => env('VITE_YG_MAIL_URL', 'http://localhost:3002'), 'port' => 3002, 'status' => 'operational'],
            ['service_key' => 'drive', 'service_name' => 'YG Drive', 'url' => env('VITE_YG_DRIVE_URL', 'http://localhost:3007'), 'port' => 3007, 'status' => 'operational'],
            ['service_key' => 'pay', 'service_name' => 'YG Pay', 'url' => env('VITE_YG_PAY_URL', 'http://localhost:3001'), 'port' => 3001, 'status' => 'operational'],
            ['service_key' => 'meet', 'service_name' => 'YG Meet', 'url' => env('VITE_YG_MEET_URL', 'http://localhost:3009'), 'port' => 3009, 'status' => 'operational'],
            ['service_key' => 'chat', 'service_name' => 'YG Chat', 'url' => env('VITE_YG_CHAT_URL', 'http://localhost:8006'), 'port' => 8006, 'status' => 'operational'],
            ['service_key' => 'docx', 'service_name' => 'YG DocX', 'url' => env('VITE_YG_DOCX_URL', 'http://localhost:8003'), 'port' => 8003, 'status' => 'operational'],
            ['service_key' => 'master', 'service_name' => 'YG Master', 'url' => env('VITE_YG_MASTER_URL', 'http://localhost:8009'), 'port' => 8009, 'status' => 'operational'],
        ];

        foreach ($services as $service) {
            YgService::updateOrCreate(
                ['service_key' => $service['service_key']],
                $service
            );
        }
    }
}
