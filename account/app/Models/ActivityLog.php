<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'type',
        'icon',
        'ip_address',
        'service',
        'device',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity for a user with intelligent device detection.
     */
    public static function record(int $userId, string $action, string $type = 'Account', string $icon = '🔔', string $service = 'YG Account', ?array $metadata = null): void
    {
        $userAgent = request()->userAgent();
        $device = static::parseUserAgent($userAgent);

        static::create([
            'user_id'    => $userId,
            'action'     => $action,
            'type'       => $type,
            'icon'       => $icon,
            'service'    => $service,
            'ip_address' => request()->ip(),
            'device'     => $device,
            'metadata'   => $metadata,
        ]);
    }

    /**
     * Parse User Agent into a readable device string (Simplified)
     */
    private static function parseUserAgent(?string $ua): string
    {
        if (!$ua) return 'Unknown Device';

        $os = 'Unknown OS';
        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Macintosh')) $os = 'macOS';
        elseif (str_contains($ua, 'iPhone')) $os = 'iOS';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'Linux')) $os = 'Linux';

        $browser = 'Unknown Browser';
        if (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
        elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Edge')) $browser = 'Edge';

        return "{$os} / {$browser}";
    }
}
