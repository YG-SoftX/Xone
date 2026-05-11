<?php

namespace App\Services;

use App\Models\EmailQuota;
use Illuminate\Support\Facades\Cache;

class SpamProtection
{
    /**
     * Default daily sending limit per user.
     */
    const DAILY_LIMIT = 100;

    /**
     * Rate limit key prefix.
     */
    const RATE_LIMIT_PREFIX = 'mail_rate_limit:';

    /**
     * Blocked domain patterns.
     */
    const BLOCKED_DOMAINS = [
        'tempmail.com',
        'guerrillamail.com',
        'mailinator.com',
        'throwaway.email',
    ];

    /**
     * Check if user has exceeded their daily quota.
     */
    public function hasExceededQuota(int $userId): bool
    {
        $today = now()->toDateString();
        $quota = EmailQuota::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => $today,
            ],
            [
                'daily_sent' => 0,
                'daily_limit' => self::DAILY_LIMIT,
            ]
        );

        return !$quota->hasRemainingQuota();
    }

    /**
     * Record an email sent event.
     */
    public function recordEmailSent(int $userId): void
    {
        $today = now()->toDateString();
        EmailQuota::where('user_id', $userId)
            ->where('date', $today)
            ->increment('daily_sent');
    }

    /**
     * Check rate limiting (emails per minute).
     */
    public function isRateLimited(int $userId): bool
    {
        $key = self::RATE_LIMIT_PREFIX . $userId;
        $attempts = Cache::get($key, 0);

        // Max 10 emails per minute
        if ($attempts >= 10) {
            return true;
        }

        Cache::put($key, $attempts + 1, now()->addMinute());
        return false;
    }

    /**
     * Check if recipient domain is in the blocked list.
     */
    public function isBlockedDomain(string $email): bool
    {
        $domain = strtolower(explode('@', $email)[1] ?? '');

        foreach (self::BLOCKED_DOMAINS as $blocked) {
            if (str_contains($domain, $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate email before sending (all checks combined).
     *
     * @param string|null $subject  Pass the subject to enable spam keyword check.
     * @param string|null $body     Pass the body to enable spam keyword check.
     * @return array{allowed: bool, reason: string|null}
     */
    public function validateEmail(int $userId, string $recipientEmail, ?string $subject = null, ?string $body = null): array
    {
        if ($this->hasExceededQuota($userId)) {
            return [
                'allowed' => false,
                'reason' => 'Daily email limit exceeded. Try again tomorrow.',
            ];
        }

        if ($this->isRateLimited($userId)) {
            return [
                'allowed' => false,
                'reason' => 'Too many emails sent too quickly. Please wait a moment.',
            ];
        }

        if ($this->isBlockedDomain($recipientEmail)) {
            return [
                'allowed' => false,
                'reason' => 'Sending to this domain is not allowed.',
            ];
        }

        if ($subject !== null && $body !== null && $this->isLikelySpam($subject, $body)) {
            return [
                'allowed' => false,
                'reason' => 'Message flagged as potential spam. Please review your content.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if email content is likely spam based on keywords and patterns.
     */
    public function isLikelySpam(string $subject, string $body): bool
    {
        $subject = strtolower($subject);
        $body = strtolower($body);

        $spamKeywords = [
            'buy now', 'free gift', 'win a prize', 'click here', 'urgent',
            'unpaid invoice', 'lottery', 'bitcoin', 'crypto', 'investment',
            'make money', 'work from home', 'congratulations', 'winner'
        ];

        // 1. Check subject for keywords
        foreach ($spamKeywords as $keyword) {
            if (str_contains($subject, $keyword)) return true;
        }

        // 2. Check for excessive special characters in subject
        if (preg_match_all('/[!$#%^&*]/', $subject) > 3) return true;

        // 3. Check body for multiple spam keywords
        $hitCount = 0;
        foreach ($spamKeywords as $keyword) {
            if (str_contains($body, $keyword)) $hitCount++;
        }

        if ($hitCount >= 3) return true;

        // 4. Check for hidden links or suspicious patterns
        if (str_contains($body, '<a href="http://') && !str_contains($body, 'https://')) {
            // Favor HTTPS, flag old HTTP as suspicious if combined with other factors
            if ($hitCount >= 1) return true;
        }

        return false;
    }

    /**
     * Reset rate limits for a user.
     */
    public function resetRateLimit(int $userId): void
    {
        Cache::forget(self::RATE_LIMIT_PREFIX . $userId);
    }
}
