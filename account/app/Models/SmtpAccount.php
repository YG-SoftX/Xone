<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpAccount extends Model
{
    protected $fillable = [
        'user_id',
        'domain',
        'email_address',
        'display_name',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password_encrypted',
        'encryption',
        'daily_limit',
        'emails_sent_today',
        'last_reset_date',
        'is_active',
        'is_verified',
        'dns_records',
        'verified_at',
    ];

    protected $hidden = [
        'smtp_password_encrypted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'dns_records' => 'array',
        'verified_at' => 'datetime',
        'last_reset_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['smtp_password_encrypted'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute()
    {
        return Crypt::decryptString($this->smtp_password_encrypted);
    }

    public function canSendMore(): bool
    {
        $this->resetDailyCountIfNeeded();
        return $this->emails_sent_today < $this->daily_limit;
    }

    public function incrementSentCount()
    {
        $this->resetDailyCountIfNeeded();
        $this->increment('emails_sent_today');
    }

    protected function resetDailyCountIfNeeded()
    {
        if ($this->last_reset_date !== today()) {
            $this->update([
                'emails_sent_today' => 0,
                'last_reset_date' => today(),
            ]);
        }
    }

    public function getDnsConfig(): array
    {
        return [
            'SPF' => "v=spf1 include:{$this->smtp_host} ~all",
            'DKIM' => "k=rsa; p=YOUR_PUBLIC_KEY",
            'DMARC' => "v=DMARC1; p=none; rua=mailto:dmarc@{$this->domain}",
        ];
    }
}
