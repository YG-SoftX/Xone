<?php

namespace Database\Seeders;

use App\Models\UniversalNavigationItem;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Seed the ecosystem navigation with initial authority links.
     */
    public function run(): void
    {
        $menus = [
            // ==========================================
            // YG ACCOUNT (Identity Hub)
            // ==========================================
            [
                'service_key' => 'account',
                'position' => 'header',
                'label' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'fas fa-th-large',
                'order' => 1
            ],
            [
                'service_key' => 'account',
                'position' => 'header',
                'label' => 'Security Hub',
                'url' => '/settings/security',
                'icon' => 'fas fa-shield-halved',
                'order' => 2
            ],
            [
                'service_key' => 'account',
                'position' => 'header',
                'label' => 'Vault',
                'url' => '/passwords',
                'icon' => 'fas fa-vault',
                'order' => 3
            ],
            [
                'service_key' => 'account',
                'position' => 'footer',
                'label' => 'Privacy Policy',
                'url' => '/legal/privacy',
                'order' => 1
            ],
            [
                'service_key' => 'account',
                'position' => 'footer',
                'label' => 'Terms of Authority',
                'url' => '/legal/terms',
                'order' => 2
            ],

            // ==========================================
            // YG PAY (Financial Authority)
            // ==========================================
            [
                'service_key' => 'pay',
                'position' => 'header',
                'label' => 'My Wallet',
                'url' => '/pay/dashboard',
                'icon' => 'fas fa-wallet',
                'order' => 1
            ],
            [
                'service_key' => 'pay',
                'position' => 'header',
                'label' => 'Ledger',
                'url' => '/pay/transactions',
                'icon' => 'fas fa-list-ul',
                'order' => 2
            ],
            [
                'service_key' => 'pay',
                'position' => 'header',
                'label' => 'Subscriptions',
                'url' => '/pay/subscriptions',
                'icon' => 'fas fa-crown',
                'order' => 3
            ],
            [
                'service_key' => 'pay',
                'position' => 'footer',
                'label' => 'Fee Schedule',
                'url' => '/pay/fees',
                'order' => 1
            ],
            [
                'service_key' => 'pay',
                'position' => 'footer',
                'label' => 'Compliance',
                'url' => '/pay/compliance',
                'order' => 2
            ],

            // ==========================================
            // YG MAIL (Secure Triage)
            // ==========================================
            [
                'service_key' => 'mail',
                'position' => 'header',
                'label' => 'Inbox',
                'url' => '/mail/inbox',
                'icon' => 'fas fa-inbox',
                'order' => 1
            ],
            [
                'service_key' => 'mail',
                'position' => 'header',
                'label' => 'Sent Archives',
                'url' => '/mail/sent',
                'icon' => 'fas fa-paper-plane',
                'order' => 2
            ],
            [
                'service_key' => 'mail',
                'position' => 'footer',
                'label' => 'E2E Encryption Guide',
                'url' => '/mail/encryption-guide',
                'order' => 1
            ],
            [
                'service_key' => 'mail',
                'position' => 'footer',
                'label' => 'Spam Protocol',
                'url' => '/mail/spam-protocol',
                'order' => 2
            ],
        ];

        foreach ($menus as $menu) {
            UniversalNavigationItem::updateOrCreate(
                ['service_key' => $menu['service_key'], 'label' => $menu['label'], 'position' => $menu['position']],
                $menu
            );
        }
    }
}
