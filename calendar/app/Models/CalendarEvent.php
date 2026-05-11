<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'calendar_events';

    protected $fillable = [
        'title', 'description', 'location',
        'meet_link', 'starts_at', 'ends_at', 'all_day', 'color',
        'status', 'visibility', 'recurrence_rule', 'recurrence_until',
        'parent_event_id', 'reminders',
    ];

    protected $casts = [
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'recurrence_until' => 'datetime',
        'all_day'          => 'boolean',
        'reminders'        => 'array',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class, 'event_id');
    }

    public function isRecurring(): bool
    {
        return !empty($this->recurrence_rule);
    }

    public function isToday(): bool
    {
        return $this->starts_at->isToday();
    }

    public function durationInMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /** Top-of-hour offset for calendar grid positioning (0–1439 minutes from midnight). */
    public function startMinuteOfDay(): int
    {
        return $this->starts_at->hour * 60 + $this->starts_at->minute;
    }

    public function getDisplayColorAttribute(): string
    {
        return $this->color ?? $this->calendar->color ?? '#4285f4';
    }

    public function scopeInRange($query, $start, $end)
    {
        // Cast to Carbon to ensure safe parameterized binding regardless of input type
        $startDt = \Illuminate\Support\Carbon::parse($start);
        $endDt   = \Illuminate\Support\Carbon::parse($end);
        return $query->where('starts_at', '<=', $endDt)
                     ->where('ends_at', '>=', $startDt);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
