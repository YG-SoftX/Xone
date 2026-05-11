<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPlan;

class PlanService
{
    /**
     * Get the user's active plan key (personal|business|enterprise).
     * Falls back to 'personal' if no plan record exists.
     */
    public function getPlan(User $user): string
    {
        $plan = UserPlan::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $plan?->plan ?? 'personal';
    }

    /**
     * Check whether the user's plan includes a specific feature.
     *
     * Usage: $planService->can($user, 'admin_panel')
     */
    public function can(User $user, string $feature): bool
    {
        $planKey = $this->getPlan($user);
        return (bool) config("plans.{$planKey}.features.{$feature}", false);
    }

    /**
     * Check the user's AI query daily limit from their plan.
     */
    public function aiDailyLimit(User $user): int
    {
        $planKey = $this->getPlan($user);
        return (int) config("plans.{$planKey}.ai_queries_daily", 50);
    }

    /**
     * Check the user's mail daily send limit from their plan.
     */
    public function mailDailyLimit(User $user): int
    {
        $planKey = $this->getPlan($user);
        return (int) config("plans.{$planKey}.mail_daily_limit", 100);
    }

    /**
     * Activate or update a plan for a user.
     */
    public function activate(
        User   $user,
        string $plan,
        string $billingCycle = 'monthly',
        ?string $stripeSubscriptionId = null,
        ?string $storagePack = null
    ): UserPlan {
        $planConfig  = config("plans.{$plan}");
        $packConfig  = $storagePack ? config("plans.storage_packs.{$storagePack}") : null;
        $addonBytes  = $packConfig ? $packConfig['bytes'] : 0;
        $basePrice   = $planConfig['price_monthly'] ?? 0;
        $packPrice   = $packConfig ? $packConfig['price_monthly'] : 0;

        $userPlan = UserPlan::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan'                   => $plan,
                'storage_pack'           => $storagePack,
                'storage_addon_bytes'    => $addonBytes,
                'monthly_price'          => $basePrice + $packPrice,
                'billing_cycle'          => $billingCycle,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'status'                 => 'active',
                'current_period_ends_at' => now()->addMonth(),
            ]
        );

        // Sync storage quota ceiling
        app(StorageQuotaService::class)->syncAfterPlanChange($user);

        return $userPlan;
    }

    /**
     * Cancel a user's paid plan (they revert to personal).
     */
    public function cancel(User $user): void
    {
        UserPlan::where('user_id', $user->id)
            ->update(['status' => 'cancelled', 'plan' => 'personal']);

        app(StorageQuotaService::class)->syncAfterPlanChange($user);
    }

    /**
     * All plan definitions for display (pricing page).
     */
    public function allPlans(): array
    {
        return config('plans', []);
    }
}
