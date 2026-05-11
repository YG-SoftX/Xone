<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate and Send OTP to User
     */
    public static function send(User $user, string $reason = 'verification'): bool
    {
        // Generate 6-digit code
        $code = rand(100000, 999999);
        
        // Save to user
        $user->forceFill([
            'ver_code' => $code,
            'ver_code_send_at' => Carbon::now(),
        ])->save();

        // Log the event
        ActivityLog::record($user->id, "Requested OTP for {$reason}", 'Security', '📩', 'YG Guard');

        // Send Email (We'll build this template next)
        try {
            Mail::send('emails.otp', ['code' => $code, 'user' => $user, 'reason' => $reason], function($message) use ($user) {
                $message->to($user->email)->subject('YG Guard Security Code: ' . $user->ver_code);
            });
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send OTP to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify the provided OTP code
     */
    public static function verify(User $user, string $code): bool
    {
        // Check if code matches
        if ($user->ver_code !== $code) {
            return false;
        }

        // Check if code has expired (e.g., 10 minutes)
        if ($user->ver_code_send_at && Carbon::parse($user->ver_code_send_at)->addMinutes(10)->isPast()) {
            return false;
        }

        // Clear code after successful verification
        $user->forceFill([
            'ver_code' => null,
            'ver_code_send_at' => null,
        ])->save();

        ActivityLog::record($user->id, "Successfully verified OTP", 'Security', '✅', 'YG Guard');

        return true;
    }
}
