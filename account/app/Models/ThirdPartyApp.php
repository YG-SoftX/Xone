<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ThirdPartyApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'website_url',
        'redirect_uri',
        'client_id',
        'client_secret',
        'scopes',
        'is_active',
        'last_used_at',
        'total_auth_requests',
        'total_api_calls',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'total_auth_requests' => 'integer',
        'total_api_calls' => 'integer',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * Boot the model to auto-generate client_id and secret.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($app) {
            if (empty($app->client_id)) {
                $app->client_id = 'yg_' . Str::slug($app->slug ?? $app->name) . '_' . Str::random(8);
            }
            if (empty($app->client_secret)) {
                $app->client_secret = hash('sha256', Str::random(64));
            }
            if (empty($app->slug)) {
                $app->slug = Str::slug($app->name);
            }
        });
    }

    /**
     * The user who owns this app.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Auth logs for this app.
     */
    public function authLogs()
    {
        return $this->hasMany(ThirdPartyAuthLog::class, 'app_id');
    }

    /**
     * Check if the given callback URL matches this app's redirect URI.
     */
    public function allowsCallback(string $callbackUrl): bool
    {
        $parsed = parse_url($callbackUrl);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        $callbackHost = strtolower($parsed['host']);
        $redirectHost = parse_url($this->redirect_uri, PHP_URL_HOST);

        return $callbackHost === $redirectHost || str_ends_with($callbackHost, '.' . $redirectHost);
    }

    /**
     * Verify the client secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->client_secret, hash('sha256', $secret));
    }

    /**
     * Record an auth event.
     */
    public function recordAuthEvent(string $eventType, ?int $userId = null, ?string $ip = null, ?string $ua = null, array $metadata = [])
    {
        ThirdPartyAuthLog::create([
            'app_id' => $this->id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'metadata' => $metadata,
        ]);

        if ($eventType === 'sso_validated') {
            $this->increment('total_auth_requests');
            $this->update(['last_used_at' => now()]);
        }
    }
}
