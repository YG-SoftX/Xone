<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'reply_to_id', 'body', 'type', 'attachments', 'reactions', 'is_edited', 'is_deleted',
    ];

    protected $casts = [
        'attachments' => 'array',
        'reactions'   => 'array',
        'is_edited'   => 'boolean',
        'is_deleted'  => 'boolean',
    ];

    public function space(): BelongsTo   { return $this->belongsTo(ChatSpace::class, 'space_id'); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function replyTo(): BelongsTo { return $this->belongsTo(ChatMessage::class, 'reply_to_id'); }

    public function addReaction(int $userId, string $emoji): void
    {
        $reactions = $this->reactions ?? [];
        $reactions[$emoji] = array_unique([...($reactions[$emoji] ?? []), $userId]);
        $this->update(['reactions' => $reactions]);
    }

    public function removeReaction(int $userId, string $emoji): void
    {
        $reactions = $this->reactions ?? [];
        $reactions[$emoji] = array_values(array_diff($reactions[$emoji] ?? [], [$userId]));
        if (empty($reactions[$emoji])) unset($reactions[$emoji]);
        $this->update(['reactions' => $reactions]);
    }

    public function softDelete(): void
    {
        $this->update(['is_deleted' => true, 'body' => '[Message deleted]']);
    }
}
