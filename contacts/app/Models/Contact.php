<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'nickname', 'company',
        'job_title', 'department', 'avatar', 'is_starred',
        'emails', 'phones', 'addresses', 'websites', 'social_profiles',
        'birthday', 'notes', 'linked_user_id',
    ];

    protected $casts = [
        'emails'          => 'array',
        'phones'          => 'array',
        'addresses'       => 'array',
        'websites'        => 'array',
        'social_profiles' => 'array',
        'birthday'        => 'date',
        'is_starred'      => 'boolean',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_pivot', 'contact_id', 'group_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        $emails = $this->emails ?? [];
        $primary = collect($emails)->firstWhere('is_primary', true);
        return $primary['email'] ?? ($emails[0]['email'] ?? null);
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        $phones = $this->phones ?? [];
        return $phones[0]['number'] ?? null;
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name ?? '', 0, 1)
        );
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'LIKE', "%{$term}%")
              ->orWhere('last_name', 'LIKE', "%{$term}%")
              ->orWhere('company', 'LIKE', "%{$term}%")
              ->orWhere('emails', 'LIKE', "%{$term}%");
        });
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
