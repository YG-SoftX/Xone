<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpreadsheetActivity extends Model
{
    use HasFactory;

    protected $table = 'spreadsheet_activity';

    protected $fillable = [
        'spreadsheet_id',
        'user_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Only created_at is meaningful for an activity log; updated_at is unused.
    const UPDATED_AT = null;

    /**
     * The spreadsheet this activity relates to.
     */
    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }

    /**
     * The user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity entry for a spreadsheet.
     */
    public static function recordActivity(
        int $spreadsheetId,
        int $userId,
        string $action,
        array $metadata = []
    ): self {
        return self::create([
            'spreadsheet_id' => $spreadsheetId,
            'user_id' => $userId,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope for filtering by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for recent activity.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
