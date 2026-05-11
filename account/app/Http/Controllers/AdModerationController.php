<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\ExternalSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdModerationController extends Controller
{
    /**
     * Display the moderation queue for Ads and AdSense
     */
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Imperial economic access denied.');
        }

        $pendingCampaigns = AdCampaign::with('organization')
            ->where('status', 'pending_review')
            ->get();

        $pendingSites = ExternalSite::with('organization')
            ->where('status', 'pending')
            ->get();

        return view('admin.monetization.moderation', compact('pendingCampaigns', 'pendingSites'));
    }

    /**
     * Approve an Ad Campaign
     */
    public function approveCampaign(AdCampaign $campaign)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        $campaign->update(['status' => 'active']);
        return back()->with('success', "Ad Campaign '{$campaign->title}' has been officially launched.");
    }

    /**
     * Approve an AdSense Site
     */
    public function approveSite(ExternalSite $site)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        $site->update(['status' => 'verified']);
        return back()->with('success', "Publisher Site '{$site->domain}' has been verified for YG AdSense.");
    }

    /**
     * Reject a Campaign or Site
     */
    public function reject(Request $request, $type, $id)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        
        if ($type === 'campaign') {
            AdCampaign::find($id)->update(['status' => 'rejected']);
        } else {
            ExternalSite::find($id)->update(['status' => 'blocked']);
        }

        return back()->with('error', "The {$type} has been rejected from the Imperial Network.");
    }

    /**
     * Display all campaigns across the empire
     */
    public function allCampaigns()
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        $campaigns = AdCampaign::with('organization', 'advertiser')->latest()->paginate(20);
        return view('admin.monetization.campaigns', compact('campaigns'));
    }

    /**
     * Display all AdSense sites across the empire
     */
    public function allSites()
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        $sites = ExternalSite::with('organization')->latest()->paginate(20);
        return view('admin.monetization.sites', compact('sites'));
    }

    /**
     * Update any campaign budget or status (Override Authority)
     */
    public function updateCampaign(Request $request, AdCampaign $campaign)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);
        $campaign->update($request->only(['budget', 'status', 'daily_budget']));
        return back()->with('success', "Imperial override: Campaign '{$campaign->title}' has been updated.");
    }
}
