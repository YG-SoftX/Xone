<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Allow all users to access for now, or check for specific role
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // E2E Encryption keys
        'public_key',
        'private_key',
        'encryption_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'private_key', // Never expose private key
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
            'encryption_enabled' => 'boolean',
        ];
    }

    /**
     * Check if user has E2E encryption enabled
     */
    public function hasEncryptionEnabled(): bool
    {
        return $this->encryption_enabled ?? false;
    }

    /**
     * Check if user has encryption keys set up
     */
    public function hasEncryptionKeys(): bool
    {
        return !empty($this->public_key) && !empty($this->private_key);
    }

    /**
     * Generate encryption keys for user
     */
    public function generateEncryptionKeys(): array
    {
        $encryptionService = app(\App\Services\MailEncryptionService::class);
        $keys = $encryptionService->generateKeyPair();
        
        $this->public_key = $keys['public_key'];
        $this->private_key = encrypt($keys['private_key']); // Encrypt private key with app key
        $this->encryption_enabled = true;
        $this->save();
        
        return [
            'public_key' => $keys['public_key'],
            'private_key_generated' => true,
        ];
    }

    /**
     * Get decrypted private key
     */
    public function getDecryptedPrivateKeyAttribute(): ?string
    {
        if (empty($this->private_key)) {
            return null;
        }
        
        try {
            return decrypt($this->private_key);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt private key', ['user_id' => $this->id]);
            return null;
        }
    }

    public function mails(): HasMany
    {
        return $this->hasMany(\App\Models\Mail::class);
    }
}
