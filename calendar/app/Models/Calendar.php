<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'color', 'description',
        'is_primary', 'is_visible', 'is_shared', 'type', 'timezone',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_visible' => 'boolean',
        'is_shared'  => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(CalendarShare::class);
    }

    public static function getDefaultColors(): array
    {
        return [
            '#4285f4', '#ea4335', '#34a853', '#fbbc04',
            '#ff6d00', '#46bdc6', '#7986cb', '#8e24aa',
        ];
    }
}
