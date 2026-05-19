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
                'base_url' => 'https://account.ygxone.com',
                'icon' => 'heroicon-o-users',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Mail',
                'slug' => 'yg-mail',
                'base_url' => 'https://mail.ygxone.com',
                'icon' => 'heroicon-o-envelope',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Drive',
                'slug' => 'yg-drive',
                'base_url' => 'https://drive.ygxone.com',
                'icon' => 'heroicon-o-cloud',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG DocX',
                'slug' => 'yg-docx',
                'base_url' => 'https://docs.ygxone.com',
                'icon' => 'heroicon-o-document-text',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Chat',
                'slug' => 'yg-chat',
                'base_url' => 'https://chat.ygxone.com',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Pay',
                'slug' => 'yg-pay',
                'base_url' => 'https://pay.ygxone.com',
                'icon' => 'heroicon-o-credit-card',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Meet',
                'slug' => 'yg-meet',
                'base_url' => 'https://meet.ygxone.com',
                'icon' => 'heroicon-o-video-camera',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Notes',
                'slug' => 'yg-notes',
                'base_url' => 'https://notes.ygxone.com',
                'icon' => 'heroicon-o-book-open',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Xcel',
                'slug' => 'yg-xcel',
                'base_url' => 'https://xcel.ygxone.com',
                'icon' => 'heroicon-o-table-cells',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Calendar',
                'slug' => 'yg-calendar',
                'base_url' => 'https://calendar.ygxone.com',
                'icon' => 'heroicon-o-calendar',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Contacts',
                'slug' => 'yg-contacts',
                'base_url' => 'https://contacts.ygxone.com',
                'icon' => 'heroicon-o-users',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG AI',
                'slug' => 'yg-ai',
                'base_url' => 'https://ai.ygxone.com',
                'icon' => 'heroicon-o-cpu-chip',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Master',
                'slug' => 'yg-master',
                'base_url' => 'https://master.ygxone.com',
                'icon' => 'heroicon-o-shield-check',
                'is_active' => true,
                'is_core' => true,
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
