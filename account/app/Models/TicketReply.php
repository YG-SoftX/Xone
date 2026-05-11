<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ticket_replies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_staff',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'is_staff' => 'boolean',
    ];

    /**
     * Relationship: The ticket this reply belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Relationship: The user who wrote the reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper: Check if this reply is from a staff member.
     */
    public function isFromStaff(): bool
    {
        return (bool) $this->is_staff;
    }

    /**
     * Helper: Check if this reply is from the user (non-staff).
     */
    public function isFromUser(): bool
    {
        return !$this->is_staff;
    }
}
