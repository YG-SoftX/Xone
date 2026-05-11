<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'support_tickets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'email',
        'name',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'service',
        'attachments',
        'assigned_to',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relationship: The user who created the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: The staff member assigned to the ticket.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relationship: All replies to this ticket.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    /**
     * Scope: Only open tickets.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['closed', 'resolved']);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by priority.
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Filter by service.
     */
    public function scopeByService(Builder $query, string $service): Builder
    {
        return $query->where('service', $service);
    }

    /**
     * Scope: Filter by assigned user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Helper: Generate a unique ticket number.
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Helper: Mark ticket as resolved.
     */
    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => Carbon::now(),
        ]);
    }

    /**
     * Helper: Mark ticket as closed.
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'resolved_at' => $this->resolved_at ?? Carbon::now(),
        ]);
    }

    /**
     * Helper: Add a reply to this ticket.
     */
    public function reply(int $userId, string $message, bool $isStaff = false): TicketReply
    {
        return $this->replies()->create([
            'user_id' => $userId,
            'message' => $message,
            'is_staff' => $isStaff,
        ]);
    }

    /**
     * Static: Create a new ticket with auto-generated ticket number.
     */
    public static function createTicket(array $data): self
    {
        $data['ticket_number'] = static::generateTicketNumber();

        if (!isset($data['status'])) {
            $data['status'] = 'open';
        }

        if (!isset($data['priority'])) {
            $data['priority'] = 'medium';
        }

        return static::create($data);
    }
}
