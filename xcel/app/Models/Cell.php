<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cell extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'cell_address',
        'row',
        'column',
        'value',
        'formula',
        'computed_value',
        'data_type',
        'format_json',
        'comment',
    ];

    protected $casts = [
        'format_json' => 'array',
        'row' => 'integer',
        'column' => 'integer',
    ];

    /**
     * The parent sheet.
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    /**
     * Comments on this cell.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CellComment::class, 'cell_address', 'cell_address')
            ->whereColumn('cell_comments.sheet_id', 'cells.sheet_id');
    }

    /**
     * Parse a cell address into row and column components.
     *
     * @return array{row: int, column: int}
     */
    public static function parseAddress(string $address): array
    {
        preg_match('/^([A-Z]+)(\d+)$/', strtoupper(trim($address)), $matches);

        if (count($matches) !== 3) {
            throw new \InvalidArgumentException("Invalid cell address: {$address}");
        }

        $letters = $matches[1];
        $row = (int) $matches[2];

        $column = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $column = $column * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return [
            'row' => $row,
            'column' => $column,
        ];
    }

    /**
     * Convert a column number to its letter representation.
     */
    public static function getColumnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $column--;
            $letter = chr(($column % 26) + ord('A')) . $letter;
            $column = (int) ($column / 26);
        }
        return $letter;
    }

    /**
     * Apply a formatting key-value pair to this cell.
     */
    public function applyFormat(string $key, mixed $value): self
    {
        $format = $this->format_json ?? [];
        $format[$key] = $value;
        $this->format_json = $format;
        $this->save();

        return $this;
    }

    /**
     * Check if this cell contains a formula.
     */
    public function isFormula(): bool
    {
        return $this->data_type === 'formula' || str_starts_with($this->formula ?? '', '=');
    }
}
