<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin ?? false;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function sharedDocuments()
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }
}
