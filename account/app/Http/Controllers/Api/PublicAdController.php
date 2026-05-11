<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdUnit;
use App\Models\ExternalSite;
use App\Services\AdAuctionService;
use Illuminate\Http\Request;

class PublicAdController extends Controller
{
    /**
     * Serve an ad with Global Mediation support
     */
    public function serve(Request $request)
    {
        $clientId = $request->query('client_id');
        $slotId = $request->query('slot_id');

        $site = ExternalSite::where('id', $clientId)->where('status', 'verified')->first();
        if (!$site) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);

        $unit = AdUnit::where('id', $slotId)->where('organization_id', $site->organization_id)->first();
        if (!$unit) return response()->json(['success' => false, 'message' => 'Invalid Unit'], 404);

        // Run Auction with Mediation Fallback
        $result = AdAuctionService::selectWinner($unit, auth()->user() ?? new \App\Models\User());

        if (!$result || !$result['ad']) {
            return response()->json(['success' => true, 'ad' => null]);
        }

        $ad = $result['ad'];
        
        // Handle Internal vs Mediation logging
        if ($result['type'] === 'internal') {
            $ad->increment('total_impressions');
        }

        return response()->json([
            'success' => true,
            'type' => $result['type'],
            'network' => $result['network'] ?? 'YG Ads',
            'ad' => [
                'title' => is_object($ad) ? $ad->title : $ad['title'],
                'content' => is_object($ad) ? $ad->content : $ad['content'],
                'image_url' => is_object($ad) ? \Storage::url($ad->image_url) : $ad['image_url'],
                'target_url' => is_object($ad) ? $ad->target_url : $ad['target_url'],
                'html_snippet' => is_object($ad) ? null : ($ad['html_snippet'] ?? null)
            ]
        ]);
    }
}
