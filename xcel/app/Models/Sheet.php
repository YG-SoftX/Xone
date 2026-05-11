<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'spreadsheet_id',
        'name',
        'order_index',
        'row_count',
        'column_count',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'row_count' => 'integer',
        'column_count' => 'integer',
    ];

    /**
     * The parent spreadsheet.
     */
    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }

    /**
     * All cells in this sheet.
     */
    public function cells(): HasMany
    {
        return $this->hasMany(Cell::class);
    }

    /**
     * All charts in this sheet.
     */
    public function charts(): HasMany
    {
        return $this->hasMany(Chart::class);
    }

    /**
     * All cell comments in this sheet.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CellComment::class, 'sheet_id');
    }

    /**
     * Get a cell by its address (e.g., "A1").
     */
    public function getCell(string $address): ?Cell
    {
        return $this->cells()
            ->where('cell_address', strtoupper($address))
            ->first();
    }

    /**
     * Get a cell by row and column numbers.
     */
    public function getCellAt(int $row, int $column): ?Cell
    {
        return $this->cells()
            ->where('row', $row)
            ->where('column', $column)
            ->first();
    }

    /**
     * Get all cells within a range (e.g., "A1:B10").
     *
     * @return \Illuminate\Support\Collection<Cell>
     */
    public function getRange(string $startAddress, string $endAddress): \Illuminate\Support\Collection
    {
        $startRow = Cell::parseAddress($startAddress)['row'];
        $startCol = Cell::parseAddress($startAddress)['column'];
        $endRow = Cell::parseAddress($endAddress)['row'];
        $endCol = Cell::parseAddress($endAddress)['column'];

        $minRow = min($startRow, $endRow);
        $maxRow = max($startRow, $endRow);
        $minCol = min($startCol, $endCol);
        $maxCol = max($startCol, $endCol);

        return $this->cells()
            ->whereBetween('row', [$minRow, $maxRow])
            ->whereBetween('column', [$minCol, $maxCol])
            ->get();
    }

    /**
     * Get the used range (bounding box of all non-empty cells).
     *
     * @return array{start: string, end: string}|null
     */
    public function getUsedRange(): ?array
    {
        $cells = $this->cells()
            ->whereNotNull('value')
            ->orWhereNotNull('formula')
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        if ($cells->isEmpty()) {
            return null;
        }

        $minRow = $cells->min('row');
        $maxRow = $cells->max('row');
        $minCol = $cells->min('column');
        $maxCol = $cells->max('column');

        return [
            'start' => Cell::getColumnLetter($minCol) . $minRow,
            'end' => Cell::getColumnLetter($maxCol) . $maxRow,
        ];
    }

    /**
     * Render the sheet as an HTML table.
     */
    public function toHtmlTable(): string
    {
        $cellsByAddress = $this->cells()
            ->get()
            ->keyBy('cell_address');

        $html = '<table>';

        for ($row = 1; $row <= $this->row_count; $row++) {
            $html .= '<tr>';
            for ($col = 1; $col <= $this->column_count; $col++) {
                $address = Cell::getColumnLetter($col) . $row;
                $cell = $cellsByAddress->get($address);
                $value = $cell?->computed_value ?? $cell?->value ?? '';
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
