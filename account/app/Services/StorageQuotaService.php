<?php

namespace App\Services;

use App\Models\StorageQuota;
use App\Models\User;
use App\Models\UserPlan;

class StorageQuotaService
{
    /**
     * Get or create the quota record for a user, initialised from their plan.
     */
    public function getQuota(User $user): StorageQuota
    {
        $quota = StorageQuota::firstOrCreate(
            ['user_id' => $user->id],
            ['quota_bytes' => $this->planQuota($user), 'used_bytes' => 0]
        );

        // If the quota was just read (not created) but the plan changed, sync it
        $planBytes = $this->planQuota($user);
        if ($quota->quota_bytes !== $planBytes) {
            $quota->update(['quota_bytes' => $planBytes]);
        }

        return $quota;
    }

    /**
     * Check if the user has space for a file of the given size.
     * Returns a result array so callers can generate a human-readable error.
     *
     * @return array{allowed: bool, quota: StorageQuota, needed_bytes: int}
     */
    public function canUpload(User $user, int $fileSizeBytes): array
    {
        $quota = $this->getQuota($user);

        return [
            'allowed'      => $quota->hasSpace($fileSizeBytes),
            'quota'        => $quota,
            'needed_bytes' => $fileSizeBytes,
        ];
    }

    /**
     * Deduct bytes from a user's quota (call after a successful upload).
     */
    public function consume(User $user, int $bytes): void
    {
        $this->getQuota($user)->consume($bytes);
    }

    /**
     * Release bytes back to a user's quota (call after a file deletion).
     */
    public function release(User $user, int $bytes): void
    {
        $this->getQuota($user)->release($bytes);
    }

    /**
     * Sync a user's quota ceiling when they upgrade/downgrade their plan.
     */
    public function syncAfterPlanChange(User $user): StorageQuota
    {
        $quota     = $this->getQuota($user);
        $newCeiling = $this->planQuota($user);
        $quota->update(['quota_bytes' => $newCeiling]);
        return $quota->fresh();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function planQuota(User $user): int
    {
        $plan = UserPlan::where('user_id', $user->id)->first();

        if (!$plan || !$plan->isActive()) {
            return config('plans.personal.storage_bytes', 15 * 1024 ** 3);
        }

        return $plan->total_storage_bytes;
    }
}
