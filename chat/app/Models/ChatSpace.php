<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChatSpace extends Model
{
    use HasFactory;

    protected $table = 'chat_spaces';

    protected $fillable = ['name', 'description', 'avatar', 'type', 'is_external'];
    protected $casts   = ['is_external' => 'boolean'];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'space_id')->orderBy('created_at');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatMember::class, 'space_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'space_id')
            ->where('is_deleted', false)
            ->latestOfMany();
    }

    public function isDm(): bool { return $this->type === 'dm'; }

    /** Display name for a DM — show the other person's name */
    public function displayName(int $currentUserId): string
    {
        if ($this->type !== 'dm') {
            return $this->name ?? 'Unnamed space';
        }
        $other = $this->members()
            ->where('user_id', '!=', $currentUserId)
            ->with('user')
            ->first();
        return $other?->user?->name ?? 'Direct Message';
    }

    public function unreadCount(int $userId): int
    {
        $member = $this->members()->where('user_id', $userId)->first();
        if (!$member) return 0;

        return $this->messages()
            ->where('is_deleted', false)
            ->where('user_id', '!=', $userId)
            ->when($member->last_read_at, fn ($q) => $q->where('created_at', '>', $member->last_read_at))
            ->count();
    }
}
