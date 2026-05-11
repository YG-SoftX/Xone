<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar',
        'role',
        'status',
        'storage_used',
        'yg_account_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'storage_used'      => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function driveFiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DriveFile::class);
    }

    public function driveFolders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DriveFolder::class);
    }

    public function storageQuota(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StorageQuota::class);
    }

    public function sharedFiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileShare::class, 'shared_with_id');
    }

    public function getStorageUsedForHumansAttribute(): string
    {
        return $this->formatBytes($this->storage_used ?? 0);
    }

    public function getStorageQuotaForHumansAttribute(): string
    {
        return $this->formatBytes($this->storageQuota?->quota_bytes ?? config('drive.default_quota', 15368709120));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)   return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
