<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'is_public',
        'is_system',
        'sheets_data',
    ];

    protected $casts = [
        'sheets_data' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * The user who created this template (nullable for system templates).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for public templates.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for templates in a specific category.
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for system templates.
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Apply this template to an existing spreadsheet.
     * Creates sheets and cells based on the template's sheets_data.
     */
    public function applyToSpreadsheet(int $spreadsheetId): void
    {
        $spreadsheet = Spreadsheet::findOrFail($spreadsheetId);
        $sheetsData = $this->sheets_data;

        if (!is_array($sheetsData)) {
            return;
        }

        foreach ($sheetsData as $index => $sheetData) {
            $sheet = $spreadsheet->sheets()->create([
                'name' => $sheetData['name'] ?? 'Sheet ' . ($index + 1),
                'order_index' => $index,
                'row_count' => $sheetData['row_count'] ?? 1000,
                'column_count' => $sheetData['column_count'] ?? 26,
            ]);

            if (isset($sheetData['cells']) && is_array($sheetData['cells'])) {
                foreach ($sheetData['cells'] as $cellData) {
                    $sheet->cells()->create([
                        'cell_address' => $cellData['cell_address'],
                        'row' => $cellData['row'],
                        'column' => $cellData['column'],
                        'value' => $cellData['value'] ?? null,
                        'formula' => $cellData['formula'] ?? null,
                        'computed_value' => $cellData['computed_value'] ?? null,
                        'data_type' => $cellData['data_type'] ?? 'text',
                        'format_json' => $cellData['format_json'] ?? null,
                    ]);
                }
            }
        }
    }
}
