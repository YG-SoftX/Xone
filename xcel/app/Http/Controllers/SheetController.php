<?php

namespace App\Http\Controllers;

use App\Models\Sheet;
use App\Models\Spreadsheet;
use App\Models\SpreadsheetActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SheetController extends Controller
{
    /**
     * Store a newly added sheet to the spreadsheet.
     */
    public function store(Request $request, Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'row_count' => 'nullable|integer|min:1|max:10000',
                'column_count' => 'nullable|integer|min:1|max:100',
            ]);

            $orderIndex = $spreadsheet->sheets()->max('order_index') + 1;

            $sheet = $spreadsheet->sheets()->create([
                'name' => $validated['name'],
                'order_index' => $orderIndex,
                'row_count' => $validated['row_count'] ?? 1000,
                'column_count' => $validated['column_count'] ?? 26,
            ]);

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $request->user()->id,
                'sheet_created',
                ['sheet_id' => $sheet->id, 'sheet_name' => $sheet->name]
            );

            return redirect()->back()->with('success', 'Sheet added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to add sheet to spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to add sheet. Please try again.');
        }
    }

    /**
     * Update the specified sheet (rename, reorder).
     */
    public function update(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'order_index' => 'nullable|integer|min:0',
                'row_count' => 'nullable|integer|min:1|max:10000',
                'column_count' => 'nullable|integer|min:1|max:100',
            ]);

            $sheet->update($validated);

            SpreadsheetActivity::recordActivity(
                $sheet->spreadsheet_id,
                $request->user()->id,
                'sheet_updated',
                ['sheet_id' => $sheet->id, 'fields' => array_keys($validated)]
            );

            return redirect()->back()->with('success', 'Sheet updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update sheet', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update sheet. Please try again.');
        }
    }

    /**
     * Delete the specified sheet and cascade-remove its cells.
     * Prevents deleting the last sheet in a spreadsheet.
     */
    public function delete(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->user_id === auth()->id(), 403);

        try {
            $spreadsheet = $sheet->spreadsheet;

            // Prevent deleting the last sheet
            $sheetCount = $spreadsheet->sheets()->count();
            if ($sheetCount <= 1) {
                return redirect()->back()->with('error', 'Cannot delete the last sheet. A spreadsheet must have at least one sheet.');
            }

            // If deleting the default sheet, assign a new default
            if ($spreadsheet->default_sheet_id === $sheet->id) {
                $newDefault = $spreadsheet->sheets()
                    ->where('id', '!=', $sheet->id)
                    ->orderBy('order_index')
                    ->first();

                if ($newDefault) {
                    $spreadsheet->update(['default_sheet_id' => $newDefault->id]);
                }
            }

            $sheetName = $sheet->name;
            $sheet->delete();

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $request->user()->id,
                'sheet_deleted',
                ['sheet_id' => $sheet->id, 'sheet_name' => $sheetName]
            );

            return redirect()->back()->with('success', 'Sheet deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete sheet', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete sheet. Please try again.');
        }
    }

    /**
     * Duplicate the specified sheet with all its cells and charts.
     */
    public function duplicate(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $spreadsheet = $sheet->spreadsheet;
            $orderIndex = $spreadsheet->sheets()->max('order_index') + 1;

            $newSheet = $sheet->replicate();
            $newSheet->name = $sheet->name . ' (Copy)';
            $newSheet->order_index = $orderIndex;
            $newSheet->save();

            // Duplicate all cells
            foreach ($sheet->cells as $cell) {
                $newCell = $cell->replicate();
                $newCell->sheet_id = $newSheet->id;
                $newCell->save();
            }

            // Duplicate all charts
            foreach ($sheet->charts as $chart) {
                $newChart = $chart->replicate();
                $newChart->sheet_id = $newSheet->id;
                $newChart->save();
            }

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $request->user()->id,
                'sheet_duplicated',
                ['source_sheet_id' => $sheet->id, 'new_sheet_id' => $newSheet->id]
            );

            return redirect()->back()->with('success', 'Sheet duplicated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to duplicate sheet', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to duplicate sheet. Please try again.');
        }
    }
}
