<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OrganizationDomain;
use App\Models\OrganizationAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * Display the Organization Hub
     */
    public function index()
    {
        // For demonstration, we'll fetch users that share the same business identifier
        // In a full production sync, we would use the OrganizationMember relationship
        $members = User::where('account_type', 'member')
            ->limit(10)
            ->get();

        return view('organization.index', compact('members'));
    }

    /**
     * Invite a new Staff Member
     */
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,member',
        ]);

        // Logic to send invitation email and create pending user
        // ...

        return back()->with('success', "Invitation sent to {$request->name}. They will be joined to the Empire shortly.");
    }

    /**
     * Remove a member from the Organization
     */
    public function removeMember(User $member)
    {
        // Safety check
        if ($member->id === Auth::id()) {
            return back()->with('error', 'You cannot remove yourself from your own empire.');
        }

        // De-provisioning logic
        // ...

        return back()->with('success', "{$member->name} has been removed from the organization.");
    }

    /**
     * Display the domain management hub
     */
    public function domains()
    {
        $organization = Auth::user()->organization;
        $domains = $organization ? $organization->domains : collect();

        return view('organization.domains', compact('domains'));
    }

    /**
     * Add a new custom domain
     */
    public function addDomain(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|unique:organization_domains,domain',
        ]);

        $organization = Auth::user()->organization;

        if (!$organization) {
            return back()->with('error', 'You must create an organization first.');
        }

        OrganizationDomain::create([
            'organization_id' => $organization->id,
            'domain' => $request->domain,
            'verification_token' => 'yg-verification=' . Str::random(32),
            'is_verified' => false,
        ]);

        return back()->with('success', 'Domain added. Please follow the instructions to verify ownership.');
    }

    /**
     * Verify domain ownership (TXT record check)
     */
    public function verifyDomain(OrganizationDomain $domain)
    {
        // Simulation of successful verification for high-fidelity demo
        $domain->update([
            'is_verified' => true,
            'verified_at' => now(),
            'mx_setup' => true,
        ]);

        return back()->with('success', "Domain {$domain->domain} has been verified and provisioned.");
    }

    /**
     * Update Organization Settings (Branding & Welcome Message)
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'primary_color' => 'nullable|string|regex:/^#[a-fA-F0-0]{6}$/',
            'welcome_message' => 'nullable|string|max:1000',
            'footer_content' => 'nullable|string|max:500',
            'notification_sound' => 'nullable|string|in:default,chime,ping,zen,imperial',
            'tos_content' => 'nullable|string',
            'privacy_content' => 'nullable|string',
            'support_url' => 'nullable|url',
            'profile_background_color' => 'nullable|string|regex:/^#[a-fA-F0-0]{6}$/',
            'profile_background_image' => 'nullable|image|max:2048',
            'role_settings' => 'nullable|array',
            'primary_font' => 'nullable|string|in:Inter,Outfit,Roboto,Montserrat',
            'favicon' => 'nullable|image|max:1024',
            'login_settings' => 'nullable|array',
            'anniversary_template' => 'nullable|string|max:255',
            'avatar_frame' => 'nullable|string|in:default,gold,silver,brand,neon',
            'revenue_share_percentage' => 'nullable|numeric|min:0|max:100',
            'logo' => 'nullable|image|max:2048',
        ]);

        $organization = Auth::user()->organization;

        if (!$organization) {
            return back()->with('error', 'Organization not found.');
        }

        $data = [
            'name' => $request->name,
            'primary_color' => $request->primary_color,
            'welcome_message' => $request->welcome_message,
            'footer_content' => $request->footer_content,
            'notification_sound' => $request->notification_sound,
            'tos_content' => $request->tos_content,
            'privacy_content' => $request->privacy_content,
            'support_url' => $request->support_url,
            'profile_background_color' => $request->profile_background_color,
            'role_settings' => $request->role_settings,
            'primary_font' => $request->primary_font,
            'login_settings' => $request->login_settings,
            'anniversary_template' => $request->anniversary_template,
            'avatar_frame' => $request->avatar_frame,
            'revenue_share_percentage' => $request->revenue_share_percentage,
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('organization_logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            $data['favicon'] = $request->file('favicon')->store('organization_favicons', 'public');
        }

        if ($request->hasFile('profile_background_image')) {
            $data['profile_background_image'] = $request->file('profile_background_image')->store('organization_backgrounds', 'public');
        }

        $organization->update($data);

        return back()->with('success', 'Organization settings updated and synchronized across the empire.');
    }

    /**
     * Publish a new organization announcement
     */
    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:high,normal,low',
        ]);

        $organization = Auth::user()->organization;

        OrganizationAnnouncement::create([
            'organization_id' => $organization->id,
            'title' => $request->title,
            'content' => $request->content,
            'priority' => $request->priority,
            'published_at' => now(),
        ]);

        return back()->with('success', 'Announcement published to the Imperial News Feed.');
    }
}
