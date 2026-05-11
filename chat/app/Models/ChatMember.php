<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMember extends Model
{
    protected $table    = 'chat_members';
    protected $fillable = ['role', 'last_read_at', 'is_muted'];
    protected $casts    = ['last_read_at' => 'datetime', 'is_muted' => 'boolean'];

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function space(): BelongsTo { return $this->belongsTo(ChatSpace::class, 'space_id'); }

    public function markRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }
}
