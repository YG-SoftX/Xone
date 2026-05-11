<?php

namespace Database\Seeders;

use App\Models\Documentation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'category' => 'account',
                'title' => 'How to reset your YG Account password',
                'summary' => 'Forgot your password? Follow these steps to securely reset it using your recovery email or phone.',
                'content' => '<h3>Resetting your password</h3><p>If you cannot remember your password, you can reset it by following these steps:</p><ul><li>Go to the YG Account login page.</li><li>Click on "Forgot Password?".</li><li>Enter your registered email address.</li><li>Check your inbox for a reset link.</li><li>Follow the instructions to create a new, strong password.</li></ul>',
            ],
            [
                'category' => 'account',
                'title' => 'Enable Two-Factor Authentication (2FA)',
                'summary' => 'Add an extra layer of security to your account by requiring a code from your mobile device.',
                'content' => '<h3>Protecting your account with 2FA</h3><p>Two-factor authentication adds an extra layer of security to your YG Account. When enabled, you will need both your password and a verification code to log in.</p><p>We support Authenticator apps (like Google Authenticator) and SMS verification.</p>',
            ],
            [
                'category' => 'pay',
                'title' => 'Getting started with YG Pay Wallet',
                'summary' => 'Learn how to set up your digital wallet and start sending or receiving secure payments.',
                'content' => '<h3>Your YG Pay Wallet</h3><p>YG Pay is the integrated payment system for the YGXone ecosystem. To get started:</p><ol><li>Navigate to the Pay module.</li><li>Click on "Initialize Wallet".</li><li>Link your bank account or credit card.</li><li>Verify your identity via the KYC process.</li></ol>',
            ],
            [
                'category' => 'mail',
                'title' => 'Understanding End-to-End Encryption',
                'summary' => 'Your privacy is our priority. Learn how YG Mail protects your messages from prying eyes.',
                'content' => '<h3>Sovereign Privacy</h3><p>Every email sent through YG Mail is protected by military-grade end-to-end encryption. This means only you and the recipient can read the message content.</p><p>Not even our system administrators can see your private communications.</p>',
            ],
        ];

        foreach ($articles as $article) {
            Documentation::create([
                'category' => $article['category'],
                'slug' => Str::slug($article['title']),
                'title' => $article['title'],
                'summary' => $article['summary'],
                'content' => $article['content'],
                'is_published' => true,
                'order' => 0,
            ]);
        }
    }
}
