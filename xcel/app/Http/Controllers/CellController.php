<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use App\Models\Sheet;
use App\Services\FormulaEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CellController extends Controller
{
    /**
     * Batch update multiple cells.
     * Receives an array of cell updates: address, value, formula, format.
     * When formula field is provided, stores formula and sets computed_value.
     * When value changes, recomputes any dependent formulas.
     */
    public function batchUpdate(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'updates' => 'required|array',
                'updates.*.address' => 'required|string',
                'updates.*.value' => 'nullable',
                'updates.*.formula' => 'nullable|string',
                'updates.*.format' => 'nullable|array',
                'updates.*.data_type' => 'nullable|string|in:text,number,boolean,date,formula',
            ]);

            $updatedCells = [];

            foreach ($validated['updates'] as $update) {
                $address = strtoupper(trim($update['address']));
                $parsed = Cell::parseAddress($address);

                $cell = Cell::firstOrCreate(
                    [
                        'sheet_id' => $sheet->id,
                        'cell_address' => $address,
                    ],
                    [
                        'row' => $parsed['row'],
                        'column' => $parsed['column'],
                    ]
                );

                // If formula is provided, store formula and compute value
                if (isset($update['formula']) && $update['formula'] !== null) {
                    $formula = $update['formula'];

                    // Ensure formula starts with =
                    if (!str_starts_with($formula, '=')) {
                        $formula = '=' . $formula;
                    }

                    $cell->formula = $formula;
                    $cell->data_type = 'formula';

                    // Evaluate the formula
                    try {
                        $computedValue = FormulaEngine::evaluate($formula, $sheet);
                        $cell->computed_value = $computedValue;
                    } catch (\Exception $e) {
                        Log::warning('Formula evaluation failed', [
                            'formula' => $formula,
                            'cell' => $address,
                            'error' => $e->getMessage(),
                        ]);
                        $cell->computed_value = '#ERROR!';
                    }
                }

                // If value is provided (and no formula), set value directly
                if (isset($update['value']) && !isset($update['formula'])) {
                    $cell->value = $update['value'];

                    // Infer data type
                    if (!isset($update['data_type'])) {
                        $cell->data_type = $this->inferDataType($update['value']);
                    }

                    // Clear formula if setting raw value
                    if ($cell->formula) {
                        $cell->formula = null;
                    }

                    // Recompute dependent formulas
                    $this->recomputeDependents($sheet, $address);
                }

                // Apply format if provided
                if (isset($update['format']) && is_array($update['format'])) {
                    $existingFormat = $cell->format_json ?? [];
                    $cell->format_json = array_merge($existingFormat, $update['format']);
                }

                // Set data type if explicitly provided
                if (isset($update['data_type'])) {
                    $cell->data_type = $update['data_type'];
                }

                $cell->save();
                $updatedCells[] = $cell;
            }

            return redirect()->back()->with('success', count($updatedCells) . ' cell(s) updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to batch update cells', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update cells. Please try again.');
        }
    }

    /**
     * Update a single cell.
     */
    public function updateSingle(Request $request, Sheet $sheet, string $address)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'value' => 'nullable',
                'formula' => 'nullable|string',
                'format' => 'nullable|array',
                'data_type' => 'nullable|string|in:text,number,boolean,date,formula',
            ]);

            $address = strtoupper(trim($address));
            $parsed = Cell::parseAddress($address);

            $cell = Cell::firstOrCreate(
                [
                    'sheet_id' => $sheet->id,
                    'cell_address' => $address,
                ],
                [
                    'row' => $parsed['row'],
                    'column' => $parsed['column'],
                ]
            );

            // Handle formula
            if (isset($validated['formula']) && $validated['formula'] !== null) {
                $formula = $validated['formula'];
                if (!str_starts_with($formula, '=')) {
                    $formula = '=' . $formula;
                }

                $cell->formula = $formula;
                $cell->data_type = 'formula';

                try {
                    $cell->computed_value = FormulaEngine::evaluate($formula, $sheet);
                } catch (\Exception $e) {
                    Log::warning('Formula evaluation failed', [
                        'formula' => $formula,
                        'cell' => $address,
                        'error' => $e->getMessage(),
                    ]);
                    $cell->computed_value = '#ERROR!';
                }
            } elseif (isset($validated['value'])) {
                $cell->value = $validated['value'];

                if (!isset($validated['data_type'])) {
                    $cell->data_type = $this->inferDataType($validated['value']);
                }

                if ($cell->formula) {
                    $cell->formula = null;
                }

                $this->recomputeDependents($sheet, $address);
            }

            if (isset($validated['format']) && is_array($validated['format'])) {
                $existingFormat = $cell->format_json ?? [];
                $cell->format_json = array_merge($existingFormat, $validated['format']);
            }

            if (isset($validated['data_type'])) {
                $cell->data_type = $validated['data_type'];
            }

            $cell->save();

            return redirect()->back()->with('success', 'Cell ' . $address . ' updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update single cell', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
                'address' => $address ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to update cell. Please try again.');
        }
    }

    /**
     * Get a single cell's data.
     */
    public function getCell(Sheet $sheet, string $address)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);

        try {
            $address = strtoupper(trim($address));

            $cell = Cell::where('sheet_id', $sheet->id)
                ->where('cell_address', $address)
                ->first();

            if (!$cell) {
                return response()->json([
                    'cell_address' => $address,
                    'value' => null,
                    'formula' => null,
                    'computed_value' => null,
                    'data_type' => 'text',
                    'format_json' => null,
                ]);
            }

            return response()->json($cell);
        } catch (\Exception $e) {
            Log::error('Failed to get cell data', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
                'address' => $address ?? null,
            ]);

            return response()->json(['error' => 'Failed to retrieve cell data.'], 500);
        }
    }

    /**
     * Get all cells within a range (e.g., A1:C10).
     */
    public function getRange(Sheet $sheet, string $start, string $end)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);

        try {
            $start = strtoupper(trim($start));
            $end = strtoupper(trim($end));

            $startParsed = Cell::parseAddress($start);
            $endParsed = Cell::parseAddress($end);

            $minRow = min($startParsed['row'], $endParsed['row']);
            $maxRow = max($startParsed['row'], $endParsed['row']);
            $minCol = min($startParsed['column'], $endParsed['column']);
            $maxCol = max($startParsed['column'], $endParsed['column']);

            $cells = Cell::where('sheet_id', $sheet->id)
                ->whereBetween('row', [$minRow, $maxRow])
                ->whereBetween('column', [$minCol, $maxCol])
                ->get();

            // Build a complete grid including empty cells
            $grid = [];
            for ($row = $minRow; $row <= $maxRow; $row++) {
                for ($col = $minCol; $col <= $maxCol; $col++) {
                    $address = Cell::getColumnLetter($col) . $row;
                    $cell = $cells->firstWhere('cell_address', $address);

                    $grid[$address] = $cell ? [
                        'cell_address' => $cell->cell_address,
                        'value' => $cell->value,
                        'formula' => $cell->formula,
                        'computed_value' => $cell->computed_value,
                        'data_type' => $cell->data_type,
                        'format_json' => $cell->format_json,
                    ] : [
                        'cell_address' => $address,
                        'value' => null,
                        'formula' => null,
                        'computed_value' => null,
                        'data_type' => 'text',
                        'format_json' => null,
                    ];
                }
            }

            return response()->json([
                'range' => $start . ':' . $end,
                'cells' => $grid,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cell range', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
                'start' => $start ?? null,
                'end' => $end ?? null,
            ]);

            return response()->json(['error' => 'Failed to retrieve cell range.'], 500);
        }
    }

    /**
     * Clear the values of specified cells.
     */
    public function clearCells(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'addresses' => 'required|array',
                'addresses.*' => 'required|string',
            ]);

            $clearedCount = 0;

            foreach ($validated['addresses'] as $address) {
                $address = strtoupper(trim($address));

                $cell = Cell::where('sheet_id', $sheet->id)
                    ->where('cell_address', $address)
                    ->first();

                if ($cell) {
                    $cell->update([
                        'value' => null,
                        'formula' => null,
                        'computed_value' => null,
                        'data_type' => 'text',
                    ]);
                    $clearedCount++;
                }
            }

            return redirect()->back()->with('success', $clearedCount . ' cell(s) cleared.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to clear cells', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to clear cells. Please try again.');
        }
    }

    /**
     * Recompute all formula cells that depend on the changed cell address.
     *
     * All cells are preloaded into memory once so FormulaEngine can resolve
     * references from the in-memory collection rather than firing a DB query
     * per cell reference (avoids the previous O(n²) query pattern).
     */
    protected function recomputeDependents(Sheet $sheet, string $address): void
    {
        // Load every cell in this sheet once — FormulaEngine reads these via Sheet::getRange()
        // which queries the DB per call. We re-set the relation so Eloquent serves from cache.
        $sheet->setRelation('cells', $sheet->cells()->get());

        $formulaCells = $sheet->cells
            ->filter(fn ($c) => $c->data_type === 'formula' && $c->formula !== null);

        $changedAddress = strtoupper($address);

        foreach ($formulaCells as $formulaCell) {
            $dependencies = array_map('strtoupper', FormulaEngine::getDependencies($formulaCell->formula));

            if (!in_array($changedAddress, $dependencies)) {
                continue;
            }

            try {
                $formulaCell->computed_value = FormulaEngine::evaluate($formulaCell->formula, $sheet);
                $formulaCell->save();
            } catch (\Exception $e) {
                Log::warning('Dependent formula recomputation failed', [
                    'formula' => $formulaCell->formula,
                    'cell'    => $formulaCell->cell_address,
                    'error'   => $e->getMessage(),
                ]);
                $formulaCell->computed_value = '#ERROR!';
                $formulaCell->save();
            }
        }
    }

    /**
     * Infer the data type from a value.
     */
    protected function inferDataType(mixed $value): string
    {
        if (is_null($value)) {
            return 'text';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        // Try to parse as date
        if (is_string($value) && strtotime($value) !== false) {
            return 'date';
        }

        return 'text';
    }
}
