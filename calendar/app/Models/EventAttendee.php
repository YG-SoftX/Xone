<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    protected $fillable = [
        'email', 'name', 'response', 'is_organizer', 'is_optional', 'responded_at',
    ];

    protected $casts = [
        'is_organizer' => 'boolean',
        'is_optional'  => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function accept(): void
    {
        $this->update(['response' => 'accepted', 'responded_at' => now()]);
    }

    public function decline(): void
    {
        $this->update(['response' => 'declined', 'responded_at' => now()]);
    }
}
