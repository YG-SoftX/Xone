<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class SecuritySentinelService
{
    protected $notificationService;

    public function __construct(UnifiedNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Alert user of a new login event
     */
    public function alertNewLogin(User $user, string $ip): void
    {
        // Check if this IP has been seen before in activity logs
        $previousLogin = ActivityLog::where('user_id', $user->id)
            ->where('action', 'Sign in')
            ->where('ip_address', '!=', $ip)
            ->exists();

        if ($previousLogin) {
            $this->notificationService->createNotification(
                $user->id,
                'mail', // Security alerts usually go to mail/account
                'security_alert',
                [
                    'title' => 'New sign-in to your YG Account',
                    'message' => "Your account was just accessed from a new IP address: {$ip}. If this wasn't you, please secure your account immediately.",
                    'action_url' => 'https://account.ygxone.com/settings/security',
                    'icon' => 'fa-shield-alt',
                    'priority' => 'high',
                    'metadata' => [
                        'ip' => $ip,
                        'device' => Request::header('User-Agent'),
                    ]
                ]
            );
        }
    }

    /**
     * Alert user of a password change
     */
    public function alertPasswordChange(User $user): void
    {
        $this->notificationService->createNotification(
            $user->id,
            'mail',
            'security_alert',
            [
                'title' => 'Security alert: Password changed',
                'message' => 'The password for your YG Account was recently changed. If you did not make this change, please recover your account.',
                'action_url' => 'https://account.ygxone.com/passwords/reset',
                'icon' => 'fa-key',
                'priority' => 'urgent',
            ]
        );
    }

    /**
     * Alert user of 2FA status change
     */
    public function alertTwoFactorChange(User $user, bool $enabled): void
    {
        $status = $enabled ? 'enabled' : 'disabled';
        $this->notificationService->createNotification(
            $user->id,
            'mail',
            'security_alert',
            [
                'title' => "Security alert: 2FA {$status}",
                'message' => "Two-Factor Authentication has been {$status} on your account.",
                'action_url' => 'https://account.ygxone.com/settings/security',
                'icon' => $enabled ? 'fa-lock' : 'fa-unlock',
                'priority' => 'high',
            ]
        );
    }
}
