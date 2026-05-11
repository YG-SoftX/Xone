<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

/**
 * PricingPlansSeeder
 * 
 * Seeds pricing plans for developer API subscriptions.
 */
class PricingPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Free Tier
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for testing and small projects',
                'price' => 0,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 0,
                'features' => [
                    '1,000 API requests/month',
                    '100 requests/hour rate limit',
                    'Basic support (community)',
                    '1 project',
                    'Standard latency',
                ],
                'quotas' => [
                    'hourly_limit' => 100,
                    'daily_limit' => 1000,
                    'monthly_limit' => 1000,
                    'max_projects' => 1,
                    'max_webhooks' => 2,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // Starter Tier
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For growing applications with moderate usage',
                'price' => 29,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    '50,000 API requests/month',
                    '1,000 requests/hour rate limit',
                    'Email support',
                    '5 projects',
                    'Priority processing',
                    'Advanced analytics',
                ],
                'quotas' => [
                    'hourly_limit' => 1000,
                    'daily_limit' => 10000,
                    'monthly_limit' => 50000,
                    'max_projects' => 5,
                    'max_webhooks' => 10,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            
            // Professional Tier
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For production applications with high traffic',
                'price' => 99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    '500,000 API requests/month',
                    '5,000 requests/hour rate limit',
                    'Priority email & chat support',
                    'Unlimited projects',
                    'Premium processing',
                    'Real-time analytics',
                    'Custom webhooks',
                    'SLA guarantee (99.9%)',
                ],
                'quotas' => [
                    'hourly_limit' => 5000,
                    'daily_limit' => 50000,
                    'monthly_limit' => 500000,
                    'max_projects' => -1, // unlimited
                    'max_webhooks' => 50,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            
            // Enterprise Tier
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large-scale applications requiring custom solutions',
                'price' => 499,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'features' => [
                    'Unlimited API requests',
                    'Custom rate limits',
                    '24/7 dedicated support',
                    'Unlimited everything',
                    'Highest priority processing',
                    'Custom SLA (99.99%)',
                    'Dedicated infrastructure',
                    'Custom integrations',
                    'Account manager',
                ],
                'quotas' => [
                    'hourly_limit' => -1, // unlimited
                    'daily_limit' => -1,
                    'monthly_limit' => -1,
                    'max_projects' => -1,
                    'max_webhooks' => -1,
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            PricingPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Pricing Plans seeded successfully!');
    }
}
