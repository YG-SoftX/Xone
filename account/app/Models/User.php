<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' || $this->role === 'super_admin';
    }

    /**
     * The attributes that are mass assignable.
     *
     * Security-sensitive fields (role, kyc_status, two_factor_*, google2fa_secret,
     * recovery_codes, security_logs) are intentionally excluded. Use the explicit
     * setter methods below so mutations are always deliberate and auditable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'password',
        'username',
        'mobile_code',
        'mobile',
        'full_mobile',
        'phone',
        'avatar',
        'birthday',
        'joined_at',
        'gender',
        'address',
        'recovery_email',
        'account_type',
        'web_app_activity',
        'timeline_history',
        'device_access_logs',
        'yg_pay_ledger',
        'personalized_ads',
        'refferal_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
            'joined_at' => 'date',
            'two_factor_enabled' => 'boolean',
            'status' => 'string',
            'role' => 'string',
            'web_app_activity' => 'boolean',
            'timeline_history' => 'boolean',
            'device_access_logs' => 'boolean',
            'yg_pay_ledger' => 'boolean',
            'personalized_ads' => 'boolean',
        ];
    }

    public function savedCredentials()
    {
        return $this->hasMany(\App\Models\SavedCredential::class);
    }

    public function profile()
    {
        return $this->hasOne(\App\Models\Profile::class);
    }

    public function kyc()
    {
        return $this->hasOne(\App\Models\KYC::class)->latest();
    }

    public function wallet()
    {
        return $this->hasOne(\App\Models\PayWallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\UserSubscription::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
    }

    public function nfcTokens()
    {
        return $this->hasMany(\App\Models\NfcToken::class);
    }

    public function nfcTransactions()
    {
        return $this->hasMany(\App\Models\NfcTransaction::class);
    }

    public function sentInvoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'sender_id');
    }

    public function receivedInvoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'recipient_id');
    }

    public function smtpAccounts()
    {
        return $this->hasMany(\App\Models\SmtpAccount::class);
    }

    public function emailTemplates()
    {
        return $this->hasMany(\App\Models\EmailTemplate::class);
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\UserDevice::class);
    }

    public function thirdPartyApps()
    {
        return $this->hasMany(\App\Models\ThirdPartyApp::class);
    }

    public function authLogs()
    {
        return $this->hasMany(\App\Models\ThirdPartyAuthLog::class);
    }

    // Developer Platform relationships
    public function developerProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\DeveloperProject::class, 'owner_id');
    }

    public function projectMemberships()
    {
        return $this->hasMany(\App\Models\ProjectMember::class);
    }

    public function billingAccount()
    {
        return $this->hasOne(\App\Models\BillingAccount::class);
    }

    // ── Privileged setters (not mass-assignable) ──────────────────────────────

    public function assignRole(string $role): void
    {
        $this->forceFill(['role' => $role])->save();
    }

    public function setKycStatus(string $status): void
    {
        $this->forceFill(['kyc_status' => $status])->save();
    }

    public function setTwoFactorSecret(string $secret): void
    {
        $this->forceFill(['google2fa_secret' => $secret])->save();
    }

    public function setTwoFactorEnabled(bool $enabled): void
    {
        $this->forceFill(['two_factor_enabled' => $enabled])->save();
    }

    public function setRecoveryCodes(string $encryptedCodes): void
    {
        $this->forceFill(['recovery_codes' => $encryptedCodes])->save();
    }

    public function setStatus(string $status): void
    {
        $this->forceFill(['status' => $status])->save();
    }

    public function setSecurityCheckCompleted(): void
    {
        $this->forceFill(['security_check_completed_at' => now()])->save();
    }

    /**
     * Get the user's rank based on Stones
     */
    public function getRankAttribute(): string
    {
        if ($this->stones >= 5000) return 'Legend';
        if ($this->stones >= 1000) return 'Grand Master';
        if ($this->stones >= 500) return 'Elite';
        if ($this->stones >= 100) return 'Contributor';
        return 'Novice';
    }

    /**
     * Get the user's rank badge (HTML/Icon)
     */
    public function getRankBadgeAttribute(): string
    {
        if ($this->stones >= 5000) return '<span class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded-md text-[8px] font-black uppercase tracking-widest border border-yellow-100"><i class="fas fa-crown mr-1"></i> Legend</span>';
        if ($this->stones >= 1000) return '<span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded-md text-[8px] font-black uppercase tracking-widest border border-purple-100"><i class="fas fa-gem mr-1"></i> Grand Master</span>';
        if ($this->stones >= 500) return '<span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-md text-[8px] font-black uppercase tracking-widest border border-blue-100"><i class="fas fa-shield-alt mr-1"></i> Elite</span>';
        if ($this->stones >= 100) return '<span class="px-2 py-0.5 bg-green-50 text-green-600 rounded-md text-[8px] font-black uppercase tracking-widest border border-green-100"><i class="fas fa-pen mr-1"></i> Contributor</span>';
        return '<span class="px-2 py-0.5 bg-gray-50 text-gray-400 rounded-md text-[8px] font-black uppercase tracking-widest border border-gray-100">Novice</span>';
    }

    /**
     * Accessor for userImage (Unified across apps)
     */
    public function getUserImageAttribute() {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&background=random&color=fff";
    }
}
