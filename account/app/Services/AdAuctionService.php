<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdUnit;
use App\Models\User;

class AdAuctionService
{
    /**
     * Perform a "Sovereign Auction" with Global Mediation Fallback
     */
    public static function selectWinner(AdUnit $unit, User $viewer)
    {
        // 1. Attempt to find Internal YG Ads
        $internalWinner = self::runInternalAuction($unit, $viewer);

        if ($internalWinner) {
            return [
                'type' => 'internal',
                'ad' => $internalWinner
            ];
        }

        // 2. Fallback to Global Mediation (Third-Party Networks)
        // In production, this would call APIs of Google AdMob, Meta, etc.
        return self::triggerMediation($unit, $viewer);
    }

    private static function runInternalAuction(AdUnit $unit, User $viewer)
    {
        return AdCampaign::where('status', 'active')
            ->where(function($query) use ($viewer) {
                $query->whereNull('targeting_criteria')
                      ->orWhere('targeting_criteria', 'LIKE', '%' . ($viewer->country ?? 'global') . '%');
            })
            ->get()
            ->sortByDesc(function($campaign) {
                return $campaign->bid_amount;
            })->first();
    }

    private static function triggerMediation(AdUnit $unit, User $viewer)
    {
        // Simulated Mediation Adapter
        // Returns a third-party ad payload
        return [
            'type' => 'mediation',
            'network' => 'Google AdMob',
            'ad' => [
                'title' => 'Global Marketplace - Powered by AdMob',
                'content' => 'High-quality external inventory filling the imperial slot.',
                'image_url' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&q=80&w=300',
                'target_url' => 'https://google.com/admob',
                'html_snippet' => null // For JS-based ads
            ]
        ];
    }
}
