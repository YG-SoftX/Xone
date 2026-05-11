<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Spreadsheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'description',
        'thumbnail',
        'status',
        'default_sheet_id',
    ];

    protected $casts = [
        'folder_id' => 'integer',
        'default_sheet_id' => 'integer',
    ];

    /**
     * The user who owns this spreadsheet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->user();
    }

    /**
     * All sheets in this spreadsheet.
     */
    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class)->orderBy('order_index');
    }

    /**
     * All shares for this spreadsheet.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(SheetShare::class);
    }

    /**
     * All charts across all sheets in this spreadsheet.
     */
    public function charts(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Chart::class, Sheet::class);
    }

    /**
     * All cell comments across all sheets.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CellComment::class, Sheet::class);
    }

    /**
     * Activity log for this spreadsheet.
     */
    public function activity(): HasMany
    {
        return $this->hasMany(SpreadsheetActivity::class);
    }

    /**
     * The default sheet.
     */
    public function defaultSheet(): HasOne
    {
        return $this->hasOne(Sheet::class, 'id', 'default_sheet_id');
    }

    /**
     * Check if a user can access (read) this spreadsheet.
     */
    public function canAccess(int $userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }

        return $this->shares()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('email', User::find($userId)?->email);
            })
            ->exists();
    }

    /**
     * Check if a user can write to this spreadsheet (owner or edit-permission share).
     */
    public function canEdit(int $userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }

        return $this->shares()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('email', User::find($userId)?->email);
            })
            ->where('permission', 'edit')
            ->exists();
    }

    /**
     * Get a specific cell from a sheet.
     */
    public function getCell(int $sheetId, string $address): ?Cell
    {
        return Cell::where('sheet_id', $sheetId)
            ->where('cell_address', strtoupper($address))
            ->first();
    }

    /**
     * Add a new sheet to this spreadsheet.
     */
    public function addSheet(string $name): Sheet
    {
        $orderIndex = $this->sheets()->max('order_index') + 1;

        $sheet = $this->sheets()->create([
            'name' => $name,
            'order_index' => $orderIndex,
        ]);

        // Set as default if no default exists
        if (!$this->default_sheet_id) {
            $this->update(['default_sheet_id' => $sheet->id]);
        }

        return $sheet;
    }

    /**
     * Duplicate this spreadsheet with all its sheets and cells.
     */
    public function duplicate(): self
    {
        $duplicate = $this->replicate();
        $duplicate->title = $this->title . ' (Copy)';
        $duplicate->status = 'draft';
        $duplicate->save();

        foreach ($this->sheets as $sheet) {
            $newSheet = $sheet->replicate();
            $newSheet->spreadsheet_id = $duplicate->id;
            $newSheet->save();

            foreach ($sheet->cells as $cell) {
                $newCell = $cell->replicate();
                $newCell->sheet_id = $newSheet->id;
                $newCell->save();
            }

            foreach ($sheet->charts as $chart) {
                $newChart = $chart->replicate();
                $newChart->sheet_id = $newSheet->id;
                $newChart->save();
            }
        }

        SpreadsheetActivity::recordActivity(
            $duplicate->id,
            $this->user_id,
            'created',
            ['duplicated_from' => $this->id]
        );

        return $duplicate;
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for user's spreadsheets including shared.
     */
    public function scopeAccessibleBy($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereHas('shares', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->orWhere('email', User::find($userId)?->email);
                });
        });
    }
}
