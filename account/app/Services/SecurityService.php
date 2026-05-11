<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SavedCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Return parsed session data for all active sessions belonging to the user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSessions(User $user): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($s) => $this->parseSession($s))
            ->toArray();
    }

    /**
     * Delete a single session owned by the user (force sign-out from that device).
     */
    public function terminateSession(User $user, string $sessionId): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();
    }

    /**
     * Generate and persist 6 fresh recovery codes for the user.
     *
     * @return string[] Plain-text codes — display once, never again.
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = array_map(fn () => Str::random(10), range(1, 6));

        $user->setRecoveryCodes(encrypt(json_encode($codes)));

        return $codes;
    }

    /**
     * Mark 2FA as enabled and generate recovery codes.
     *
     * @return string[] Plain-text recovery codes (shown to user once).
     */
    public function enable2FA(User $user, string $ip): array
    {
        $codes = $this->generateRecoveryCodes($user);

        $user->setTwoFactorEnabled(true);

        ActivityLog::record($user->id, 'Two-factor authentication enabled', '2FA', '🛡️', $ip);

        return $codes;
    }

    /**
     * Disable 2FA and wipe all associated secret/recovery data.
     */
    public function disable2FA(User $user, string $ip): void
    {
        $user->forceFill([
            'two_factor_enabled' => false,
            'google2fa_secret'   => null,
            'recovery_codes'     => null,
        ])->save();

        ActivityLog::record($user->id, 'Two-factor authentication disabled', '2FA', '🛡️', $ip);
    }

    /**
     * Store a new AES-encrypted password-manager entry for the user.
     */
    public function saveCredential(User $user, array $data, string $ip): SavedCredential
    {
        $credential = SavedCredential::create([
            'user_id'   => $user->id,
            'site_name' => $data['site_name'],
            'site_url'  => $data['site_url'] ?? null,
            'username'  => $data['username'] ?? null,
            'password'  => Crypt::encryptString($data['password']),
            'icon'      => $data['icon'] ?? null,
        ]);

        ActivityLog::record($user->id, 'Saved credential: ' . $data['site_name'], 'Password', '🔐', $ip);

        return $credential;
    }

    /**
     * Decrypt and return the stored password for a credential owned by the user.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function decryptCredential(User $user, int $credentialId): string
    {
        $credential = SavedCredential::where('user_id', $user->id)
            ->where('id', $credentialId)
            ->firstOrFail();

        return Crypt::decryptString($credential->password);
    }

    // -------------------------------------------------------------------------

    private function parseSession(object $session): array
    {
        $ua = $session->user_agent ?? '';

        $os = match (true) {
            str_contains($ua, 'Windows')   => 'Windows',
            str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Android')   => 'Android',
            str_contains($ua, 'iPhone')    => 'iOS',
            default                        => 'Unknown OS',
        };

        $browser = match (true) {
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari')  => 'Safari',
            default                      => 'Unknown Browser',
        };

        return [
            'id'          => $session->id,
            'is_current'  => $session->id === session()->getId(),
            'ip_address'  => $session->ip_address,
            'os'          => $os,
            'browser'     => $browser,
            'last_active' => date('Y-m-d H:i:s', $session->last_activity),
        ];
    }
}
