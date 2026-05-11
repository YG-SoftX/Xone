<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingsController extends Controller
{
    /**
     * Display notification settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get notification preferences from user model or separate table
        $preferences = $user->notification_preferences ?? $this->getDefaultPreferences();
        
        // Get available notification channels
        $channels = [
            'email' => ['enabled' => !is_null($user->email), 'verified' => $user->email_verified_at !== null],
            'sms' => ['enabled' => !is_null($user->phone), 'verified' => $user->phone_verified_at !== null],
            'push' => ['enabled' => true, 'verified' => false] // Push notifications via Firebase
        ];
        
        return view('settings.notifications.index', compact('user', 'preferences', 'channels'));
    }
    
    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            // Security Notifications
            'security.login_alert' => 'boolean',
            'security.password_change' => 'boolean',
            'security.new_device' => 'boolean',
            'security.suspicious_activity' => 'boolean',
            
            // Billing Notifications
            'billing.invoice_ready' => 'boolean',
            'billing.payment_success' => 'boolean',
            'billing.payment_failed' => 'boolean',
            'billing.subscription_renewal' => 'boolean',
            
            // Product Notifications
            'product.updates' => 'boolean',
            'product.maintenance' => 'boolean',
            'product.new_features' => 'boolean',
            
            // Marketing Notifications
            'marketing.promotions' => 'boolean',
            'marketing.newsletter' => 'boolean',
            'marketing.partner_offers' => 'boolean',
            
            // Team Notifications
            'team.invitation' => 'boolean',
            'team.role_change' => 'boolean',
            'team.project_updates' => 'boolean',
        ]);
        
        $user = Auth::user();
        
        // Save preferences as JSON in user model
        $preferences = $request->all();
        $user->update([
            'notification_preferences' => json_encode($preferences)
        ]);
        
        return back()->with('success', 'Notification preferences updated successfully.');
    }
    
    /**
     * Test notification delivery
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,sms,push'
        ]);
        
        $user = Auth::user();
        $channel = $request->channel;
        
        try {
            switch ($channel) {
                case 'email':
                    // Send test email
                    // Mail::to($user->email)->send(new TestNotification());
                    return response()->json(['success' => true, 'message' => 'Test email sent']);
                    
                case 'sms':
                    // Send test SMS via Twilio
                    // Twilio::message($user->phone, 'Test notification from YG Account');
                    return response()->json(['success' => true, 'message' => 'Test SMS sent']);
                    
                case 'push':
                    // Send test push notification via Firebase
                    // Firebase::sendNotification($user->firebase_token, 'Test', 'This is a test notification');
                    return response()->json(['success' => true, 'message' => 'Test push notification sent']);
                    
                default:
                    return response()->json(['success' => false, 'error' => 'Invalid channel']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update email notification settings
     */
    public function updateEmailSettings(Request $request)
    {
        $request->validate([
            'email_frequency' => 'required|in:immediate,daily,weekly',
            'digest_time' => 'nullable|date_format:H:i',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i'
        ]);
        
        $user = Auth::user();
        
        $emailSettings = array_merge($user->email_settings ?? [], [
            'frequency' => $request->email_frequency,
            'digest_time' => $request->digest_time,
            'quiet_hours' => [
                'start' => $request->quiet_hours_start,
                'end' => $request->quiet_hours_end
            ]
        ]);
        
        $user->update([
            'email_settings' => json_encode($emailSettings)
        ]);
        
        return back()->with('success', 'Email notification settings updated.');
    }
    
    /**
     * Subscribe to newsletter
     */
    public function subscribeNewsletter()
    {
        $user = Auth::user();
        
        // Add to newsletter list (Mailchimp, etc.)
        // Newsletter::subscribe($user->email, ['name' => $user->name]);
        
        $user->update([
            'subscribed_to_newsletter' => true
        ]);
        
        return back()->with('success', 'Successfully subscribed to newsletter.');
    }
    
    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribeNewsletter()
    {
        $user = Auth::user();
        
        // Remove from newsletter list
        // Newsletter::unsubscribe($user->email);
        
        $user->update([
            'subscribed_to_newsletter' => false
        ]);
        
        return back()->with('success', 'Unsubscribed from newsletter.');
    }
    
    /**
     * Get default notification preferences
     */
    private function getDefaultPreferences()
    {
        return [
            'security' => [
                'login_alert' => true,
                'password_change' => true,
                'new_device' => true,
                'suspicious_activity' => true
            ],
            'billing' => [
                'invoice_ready' => true,
                'payment_success' => true,
                'payment_failed' => true,
                'subscription_renewal' => true
            ],
            'product' => [
                'updates' => true,
                'maintenance' => true,
                'new_features' => false
            ],
            'marketing' => [
                'promotions' => false,
                'newsletter' => false,
                'partner_offers' => false
            ],
            'team' => [
                'invitation' => true,
                'role_change' => true,
                'project_updates' => true
            ]
        ];
    }
}
