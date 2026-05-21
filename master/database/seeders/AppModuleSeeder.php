<?php

namespace Database\Seeders;

use App\Models\AppModule;
use Illuminate\Database\Seeder;

/**
 * AppModuleSeeder
 * 
 * Registers all 20 YG ecosystem services in the master control panel.
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
                'name' => 'YG Xone',
                'slug' => 'yg-xone',
                'base_url' => 'https://ygxone.com',
                'icon' => 'heroicon-o-globe-alt',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Account',
                'slug' => 'yg-account',
                'base_url' => 'https://account.ygxone.com',
                'icon' => 'heroicon-o-users',
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'name' => 'YG Master',
                'slug' => 'yg-master',
                'base_url' => 'https://master.ygxone.com',
                'icon' => 'heroicon-o-shield-check',
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
                'name' => 'YG Developer',
                'slug' => 'yg-developer',
                'base_url' => 'https://developer.ygxone.com',
                'icon' => 'heroicon-o-code-bracket',
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
                'name' => 'YG Notes',
                'slug' => 'yg-notes',
                'base_url' => 'https://notes.ygxone.com',
                'icon' => 'heroicon-o-book-open',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Meet',
                'slug' => 'yg-meet',
                'base_url' => 'https://meet.ygxone.com',
                'icon' => 'heroicon-o-video-camera',
                'is_active' => true,
                'is_core' => false,
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
                'name' => 'YG AI',
                'slug' => 'yg-ai',
                'base_url' => 'https://ai.ygxone.com',
                'icon' => 'heroicon-o-cpu-chip',
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
                'name' => 'YG AppStore',
                'slug' => 'yg-appstore',
                'base_url' => 'https://appstore.ygxone.com',
                'icon' => 'heroicon-o-shopping-bag',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Collect',
                'slug' => 'yg-collect',
                'base_url' => 'https://collect.ygxone.com',
                'icon' => 'heroicon-o-clipboard-document-list',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Console',
                'slug' => 'yg-console',
                'base_url' => 'https://console.ygxone.com',
                'icon' => 'heroicon-o-command-line',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Home',
                'slug' => 'yg-home',
                'base_url' => 'https://home.ygxone.com',
                'icon' => 'heroicon-o-home',
                'is_active' => true,
                'is_core' => false,
            ],
            [
                'name' => 'YG Support',
                'slug' => 'yg-support',
                'base_url' => 'https://support.ygxone.com',
                'icon' => 'heroicon-o-lifebuoy',
                'is_active' => true,
                'is_core' => false,
            ],
        ];

        foreach ($services as $serviceData) {
            AppModule::updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }

        $this->command->info("20 YG ecosystem services registered successfully!");
    }
}
