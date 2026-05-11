<?php

namespace Database\Seeders;

use App\Models\ApiProduct;
use Illuminate\Database\Seeder;

/**
 * ApiProductsSeeder
 * 
 * Seeds the API products table with all YG ecosystem services.
 */
class ApiProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'YG Account SSO',
                'slug' => 'yg-account-sso',
                'description' => 'Single Sign-On service for YG Ecosystem. Authenticate users across all YG services.',
                'category' => 'authentication',
                'base_url' => 'https://account.ygxone.com/api/v1',
                'documentation_url' => 'https://developers.ygxone.com/docs/sso',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'free',
                'features' => [
                    'OAuth 2.0 authentication',
                    'JWT token generation',
                    'User profile access',
                    'Session management',
                ],
            ],
            [
                'name' => 'YG Mail API',
                'slug' => 'yg-mail',
                'description' => 'Email service API for sending, receiving, and managing emails programmatically.',
                'category' => 'communication',
                'base_url' => 'https://account.ygxone.com/api/v1/mail',
                'documentation_url' => 'https://developers.ygxone.com/docs/mail',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'Send emails',
                    'Read inbox',
                    'Manage folders',
                    'Attachment handling',
                ],
            ],
            [
                'name' => 'YG Drive API',
                'slug' => 'yg-drive',
                'description' => 'Cloud storage API for file upload, download, sharing, and management.',
                'category' => 'storage',
                'base_url' => 'https://account.ygxone.com/api/v1/drive',
                'documentation_url' => 'https://developers.ygxone.com/docs/drive',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'File upload/download',
                    'Folder management',
                    'File sharing',
                    'Version control',
                ],
            ],
            [
                'name' => 'YG Docs API',
                'slug' => 'yg-docs',
                'description' => 'Document creation and collaboration API for text documents.',
                'category' => 'productivity',
                'base_url' => 'https://account.ygxone.com/api/v1/docs',
                'documentation_url' => 'https://developers.ygxone.com/docs/docs',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'Create documents',
                    'Real-time collaboration',
                    'Comment system',
                    'Export to PDF/DOCX',
                ],
            ],
            [
                'name' => 'YG Meet API',
                'slug' => 'yg-meet',
                'description' => 'Video conferencing API for scheduling and managing virtual meetings.',
                'category' => 'communication',
                'base_url' => 'https://account.ygxone.com/api/v1/meet',
                'documentation_url' => 'https://developers.ygxone.com/docs/meet',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'Schedule meetings',
                    'Generate meeting links',
                    'Participant management',
                    'Recording access',
                ],
            ],
            [
                'name' => 'YG Pay API',
                'slug' => 'yg-pay',
                'description' => 'Payment processing API for transactions, subscriptions, and financial operations.',
                'category' => 'payments',
                'base_url' => 'https://pay.ygxone.com/api/v1',
                'documentation_url' => 'https://developers.ygxone.com/docs/pay',
                'is_active' => true,
                'requires_approval' => true,
                'pricing_model' => 'transaction_fee',
                'features' => [
                    'Process payments',
                    'Manage subscriptions',
                    'Refund processing',
                    'Transaction history',
                ],
            ],
            [
                'name' => 'YG AI API',
                'slug' => 'yg-ai',
                'description' => 'Artificial Intelligence API for smart replies, categorization, and document summarization.',
                'category' => 'ai_ml',
                'base_url' => 'https://account.ygxone.com/api/v1/ai',
                'documentation_url' => 'https://developers.ygxone.com/docs/ai',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'usage_based',
                'features' => [
                    'Smart email replies',
                    'Document summarization',
                    'Content categorization',
                    'Natural language queries',
                ],
            ],
            [
                'name' => 'YG Forms API',
                'slug' => 'yg-forms',
                'description' => 'Form creation and response collection API.',
                'category' => 'productivity',
                'base_url' => 'https://account.ygxone.com/api/v1/forms',
                'documentation_url' => 'https://developers.ygxone.com/docs/forms',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'Create forms',
                    'Collect responses',
                    'Analytics dashboard',
                    'Custom branding',
                ],
            ],
            [
                'name' => 'YG Xcel API',
                'slug' => 'yg-xcel',
                'description' => 'Spreadsheet API for creating and manipulating spreadsheets programmatically.',
                'category' => 'productivity',
                'base_url' => 'https://account.ygxone.com/api/v1/xcel',
                'documentation_url' => 'https://developers.ygxone.com/docs/xcel',
                'is_active' => true,
                'requires_approval' => false,
                'pricing_model' => 'freemium',
                'features' => [
                    'Create spreadsheets',
                    'Cell manipulation',
                    'Chart generation',
                    'Export to CSV/XLSX',
                ],
            ],
        ];

        foreach ($products as $productData) {
            ApiProduct::updateOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );
        }

        $this->command->info('API Products seeded successfully!');
    }
}
