<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SheetShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'spreadsheet_id',
        'user_id',
        'email',
        'permission',
        'created_by',
    ];

    /**
     * The spreadsheet being shared.
     */
    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }

    /**
     * The user this share is assigned to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user looked up by email (for shares to non-registered users).
     */
    public function emailUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * The user who created this share.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this share grants edit permission.
     */
    public function canEdit(): bool
    {
        return $this->permission === 'edit';
    }

    /**
     * Check if this share grants view permission.
     */
    public function canView(): bool
    {
        return in_array($this->permission, ['view', 'edit']);
    }
}
