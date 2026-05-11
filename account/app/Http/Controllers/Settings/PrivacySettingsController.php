<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class PrivacySettingsController extends Controller
{
    /**
     * Display privacy settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's data export status
        $dataExports = \DB::table('data_exports')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get consent records
        $consents = \DB::table('user_consents')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('settings.privacy.index', compact('user', 'dataExports', 'consents'));
    }
    
    /**
     * Update privacy preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'profile_visibility' => 'required|in:public,private,friends_only',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'allow_data_analytics' => 'boolean',
            'allow_marketing_emails' => 'boolean',
            'allow_third_party_sharing' => 'boolean'
        ]);
        
        $user = Auth::user();
        
        $user->update([
            'privacy_settings' => array_merge($user->privacy_settings ?? [], [
                'profile_visibility' => $request->profile_visibility,
                'show_email' => $request->has('show_email'),
                'show_phone' => $request->has('show_phone'),
                'allow_data_analytics' => $request->has('allow_data_analytics'),
                'allow_marketing_emails' => $request->has('allow_marketing_emails'),
                'allow_third_party_sharing' => $request->has('allow_third_party_sharing')
            ])
        ]);
        
        return back()->with('success', 'Privacy preferences updated successfully.');
    }
    
    /**
     * Request data export (GDPR compliance)
     */
    public function requestDataExport()
    {
        $user = Auth::user();
        
        // Check if there's a pending export
        $pendingExport = \DB::table('data_exports')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
        
        if ($pendingExport) {
            return back()->with('warning', 'You already have a pending data export request.');
        }
        
        // Create export record
        $export = \App\Models\DataExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
        
        // Dispatch job to collect user data
        dispatch(new \App\Jobs\ExportUserData($export));
        
        return back()->with('success', 'Data export requested. You will receive an email when ready.');
    }
    
    /**
     * Download data export
     */
    public function downloadDataExport($exportId)
    {
        $user = Auth::user();
        
        $export = \DB::table('data_exports')
            ->where('id', $exportId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$export || $export->status !== 'completed') {
            abort(404, 'Export not found or not ready');
        }
        
        $filePath = storage_path("app/exports/user_{$user->id}_export_{$exportId}.zip");
        
        if (!File::exists($filePath)) {
            abort(404, 'Export file not found');
        }
        
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
    
    /**
     * Request account deletion (Right to be Forgotten - GDPR)
     */
    public function requestAccountDeletion(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
            'confirmation' => 'required|accepted'
        ]);
        
        $user = Auth::user();
        
        // Schedule account deletion (30-day grace period)
        $user->update([
            'deletion_scheduled_at' => now()->addDays(30),
            'deletion_requested_ip' => $request->ip()
        ]);
        
        // Log the deletion request
        \DB::table('account_deletion_requests')->insert([
            'user_id' => $user->id,
            'requested_at' => now(),
            'scheduled_deletion_at' => now()->addDays(30),
            'ip_address' => $request->ip(),
            'reason' => $request->input('reason', 'User requested deletion'),
            'created_at' => now()
        ]);
        
        // Send confirmation email
        // Mail::to($user->email)->send(new AccountDeletionScheduled($user));
        
        Session::flash('warning', 'Account deletion scheduled. You have 30 days to cancel this request.');
        
        return redirect('/');
    }
    
    /**
     * Cancel account deletion request
     */
    public function cancelAccountDeletion()
    {
        $user = Auth::user();
        
        if ($user->deletion_scheduled_at) {
            $user->update([
                'deletion_scheduled_at' => null,
                'deletion_requested_ip' => null
            ]);
            
            \DB::table('account_deletion_requests')
                ->where('user_id', $user->id)
                ->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);
            
            return back()->with('success', 'Account deletion request cancelled.');
        }
        
        return back()->with('error', 'No pending deletion request found.');
    }
    
    /**
     * Update cookie consent preferences
     */
    public function updateCookieConsent(Request $request)
    {
        $request->validate([
            'essential' => 'required|boolean',
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'functional' => 'boolean'
        ]);
        
        $user = Auth::user();
        
        // Record consent
        \DB::table('user_consents')->insert([
            'user_id' => $user->id,
            'consent_type' => 'cookie_preferences',
            'preferences' => json_encode($request->all()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now()
        ]);
        
        return response()->json(['success' => true]);
    }
}
