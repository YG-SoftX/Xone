<?php

namespace Database\Seeders;

use App\Models\AppModule;
use Illuminate\Database\Seeder;

/**
 * AppModuleSeeder
 * 
 * Registers all 13 YG ecosystem services in the master control panel.
 */
class AppModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'YG Account',
                'slug' => 'yg-account',
                'url' => 'https://account.ygxone.com',
                'description' => 'Central identity provider and developer portal',
                'category' => 'identity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../yg-account'),
                'api_key' => env('YG_ACCOUNT_API_KEY', ''),
            ],
            [
                'name' => 'YG Mail',
                'slug' => 'yg-mail',
                'url' => 'https://mail.ygxone.com',
                'description' => 'Email service with AI-powered features',
                'category' => 'communication',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Mail'),
                'api_key' => env('YG_MAIL_API_KEY', ''),
            ],
            [
                'name' => 'YG Drive',
                'slug' => 'yg-drive',
                'url' => 'https://drive.ygxone.com',
                'description' => 'Cloud storage and file sharing',
                'category' => 'storage',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Drive'),
                'api_key' => env('YG_DRIVE_API_KEY', ''),
            ],
            [
                'name' => 'YG DocX',
                'slug' => 'yg-docx',
                'url' => 'https://docs.ygxone.com',
                'description' => 'Document editor and collaboration',
                'category' => 'productivity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG DocX'),
                'api_key' => env('YG_DOCX_API_KEY', ''),
            ],
            [
                'name' => 'YG Chat',
                'slug' => 'yg-chat',
                'url' => 'https://chat.ygxone.com',
                'description' => 'Real-time messaging and team communication',
                'category' => 'communication',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Chat'),
                'api_key' => env('YG_CHAT_API_KEY', ''),
            ],
            [
                'name' => 'YG Pay',
                'slug' => 'yg-pay',
                'url' => 'https://pay.ygxone.com',
                'description' => 'Unified payment platform',
                'category' => 'payments',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Pay/ygpay-web'),
                'api_key' => env('YG_PAY_API_KEY', ''),
            ],
            [
                'name' => 'YG Meet',
                'slug' => 'yg-meet',
                'url' => 'https://meet.ygxone.com',
                'description' => 'Video conferencing and virtual meetings',
                'category' => 'communication',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => null, // External service
                'api_key' => env('YG_MEET_API_KEY', ''),
            ],
            [
                'name' => 'YG Notes',
                'slug' => 'yg-notes',
                'url' => 'https://notes.ygxone.com',
                'description' => 'Note-taking and knowledge management',
                'category' => 'productivity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Notes'),
                'api_key' => env('YG_NOTES_API_KEY', ''),
            ],
            [
                'name' => 'YG Xcel',
                'slug' => 'yg-xcel',
                'url' => 'https://xcel.ygxone.com',
                'description' => 'Spreadsheet application',
                'category' => 'productivity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Xcel'),
                'api_key' => env('YG_XCEL_API_KEY', ''),
            ],
            [
                'name' => 'YG Calendar',
                'slug' => 'yg-calendar',
                'url' => 'https://calendar.ygxone.com',
                'description' => 'Calendar and scheduling',
                'category' => 'productivity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Calendar'),
                'api_key' => env('YG_CALENDAR_API_KEY', ''),
            ],
            [
                'name' => 'YG Contacts',
                'slug' => 'yg-contacts',
                'url' => 'https://contacts.ygxone.com',
                'description' => 'Contact management',
                'category' => 'productivity',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../YG Contacts'),
                'api_key' => env('YG_CONTACTS_API_KEY', ''),
            ],
            [
                'name' => 'YG AI',
                'slug' => 'yg-ai',
                'url' => 'https://ai.ygxone.com',
                'description' => 'Artificial intelligence services',
                'category' => 'ai_ml',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('../yg-ai'),
                'api_key' => env('YG_AI_API_KEY', ''),
            ],
            [
                'name' => 'YG Master',
                'slug' => 'yg-master',
                'url' => 'https://master.ygxone.com',
                'description' => 'Super admin control panel',
                'category' => 'administration',
                'is_active' => true,
                'status' => 'healthy',
                'installation_path' => base_path('.'),
                'api_key' => env('YG_MASTER_API_KEY', ''),
            ],
        ];

        foreach ($services as $serviceData) {
            AppModule::updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }

        $this->command->info("13 YG ecosystem services registered successfully!");
    }
}
