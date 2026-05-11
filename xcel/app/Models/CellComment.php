<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'cell_address',
        'user_id',
        'content',
        'resolved',
        'resolved_by',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_by' => 'integer',
    ];

    /**
     * The parent sheet.
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    /**
     * The user who created this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who resolved this comment.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Mark this comment as resolved.
     */
    public function resolve(int $userId): self
    {
        $this->resolved = true;
        $this->resolved_by = $userId;
        $this->save();

        return $this;
    }
}
