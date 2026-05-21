<?php

namespace Database\Seeders;

use App\Models\UniversalFooterItem;
use Illuminate\Database\Seeder;

class UniversalFooterItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Global footer items (shown across all services)
        $globalItems = [
            [
                'service_key' => 'global',
                'position' => 'footer',
                'label' => 'Privacy Policy',
                'url' => '/privacy',
                'icon' => 'fas fa-shield-alt',
                'order' => 1,
                'is_active' => true,
                'is_external' => false,
                'metadata' => null,
            ],
            [
                'service_key' => 'global',
                'position' => 'footer',
                'label' => 'Terms of Service',
                'url' => '/terms',
                'icon' => 'fas fa-file-contract',
                'order' => 2,
                'is_active' => true,
                'is_external' => false,
                'metadata' => null,
            ],
            [
                'service_key' => 'global',
                'position' => 'footer',
                'label' => 'Support Center',
                'url' => 'https://support.ygxone.com',
                'icon' => 'fas fa-life-ring',
                'order' => 3,
                'is_active' => true,
                'is_external' => true,
                'metadata' => ['tooltip' => 'Get help from our support team'],
            ],
            [
                'service_key' => 'global',
                'position' => 'footer',
                'label' => 'Documentation',
                'url' => 'https://docs.ygxone.com',
                'icon' => 'fas fa-book',
                'order' => 4,
                'is_active' => true,
                'is_external' => true,
                'metadata' => null,
            ],
            [
                'service_key' => 'global',
                'position' => 'footer',
                'label' => 'Status Page',
                'url' => 'https://status.ygxone.com',
                'icon' => 'fas fa-chart-line',
                'order' => 5,
                'is_active' => true,
                'is_external' => true,
                'metadata' => ['tooltip' => 'Check system status'],
            ],
        ];

        // Mail-specific footer items
        $mailItems = [
            [
                'service_key' => 'mail',
                'position' => 'footer',
                'label' => 'Email Security Guide',
                'url' => '/security',
                'icon' => 'fas fa-lock',
                'order' => 1,
                'is_active' => true,
                'is_external' => false,
                'metadata' => null,
            ],
            [
                'service_key' => 'mail',
                'position' => 'footer',
                'label' => 'Encryption Info',
                'url' => '/encryption',
                'icon' => 'fas fa-key',
                'order' => 2,
                'is_active' => true,
                'is_external' => false,
                'metadata' => null,
            ],
        ];

        // Drive-specific footer items
        $driveItems = [
            [
                'service_key' => 'drive',
                'position' => 'footer',
                'label' => 'Storage Plans',
                'url' => '/plans',
                'icon' => 'fas fa-cloud-upload-alt',
                'order' => 1,
                'is_active' => true,
                'is_external' => false,
                'metadata' => null,
            ],
        ];

        // Insert all items
        foreach (array_merge($globalItems, $mailItems, $driveItems) as $item) {
            UniversalFooterItem::updateOrCreate(
                [
                    'service_key' => $item['service_key'],
                    'url' => $item['url'],
                ],
                $item
            );
        }

        $this->command->info('✅ Universal footer items seeded successfully!');
        $this->command->info('   - Global items: ' . count($globalItems));
        $this->command->info('   - Mail items: ' . count($mailItems));
        $this->command->info('   - Drive items: ' . count($driveItems));
    }
}
