<?php

namespace App\Http\Controllers;

use App\Models\Chart;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ChartController extends Controller
{
    /**
     * Create a new chart on a sheet.
     */
    public function store(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'chart_type' => 'required|string|in:bar,line,pie,area,scatter,doughnut,radar',
                'title' => 'required|string|max:255',
                'data_range' => 'required|string|regex:/^[A-Z]+\d+:[A-Z]+\d+$/i',
                'position_x' => 'nullable|integer|min:0',
                'position_y' => 'nullable|integer|min:0',
                'width' => 'nullable|integer|min:100|max:2000',
                'height' => 'nullable|integer|min:100|max:2000',
                'config' => 'nullable|array',
            ]);

            $chart = $sheet->charts()->create([
                'chart_type' => $validated['chart_type'],
                'title' => $validated['title'],
                'data_range' => strtoupper($validated['data_range']),
                'position_x' => $validated['position_x'] ?? 0,
                'position_y' => $validated['position_y'] ?? 0,
                'width' => $validated['width'] ?? 400,
                'height' => $validated['height'] ?? 300,
                'config_json' => $validated['config'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Chart created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create chart', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to create chart. Please try again.');
        }
    }

    /**
     * Update the specified chart.
     */
    public function update(Request $request, Chart $chart)
    {
        abort_unless($chart->sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'chart_type' => 'nullable|string|in:bar,line,pie,area,scatter,doughnut,radar',
                'title' => 'nullable|string|max:255',
                'data_range' => 'nullable|string|regex:/^[A-Z]+\d+:[A-Z]+\d+$/i',
                'position_x' => 'nullable|integer|min:0',
                'position_y' => 'nullable|integer|min:0',
                'width' => 'nullable|integer|min:100|max:2000',
                'height' => 'nullable|integer|min:100|max:2000',
                'config' => 'nullable|array',
            ]);

            $updateData = [];

            if (isset($validated['chart_type'])) {
                $updateData['chart_type'] = $validated['chart_type'];
            }
            if (isset($validated['title'])) {
                $updateData['title'] = $validated['title'];
            }
            if (isset($validated['data_range'])) {
                $updateData['data_range'] = strtoupper($validated['data_range']);
            }
            if (isset($validated['position_x'])) {
                $updateData['position_x'] = $validated['position_x'];
            }
            if (isset($validated['position_y'])) {
                $updateData['position_y'] = $validated['position_y'];
            }
            if (isset($validated['width'])) {
                $updateData['width'] = $validated['width'];
            }
            if (isset($validated['height'])) {
                $updateData['height'] = $validated['height'];
            }
            if (isset($validated['config'])) {
                $updateData['config_json'] = $validated['config'];
            }

            $chart->update($updateData);

            return redirect()->back()->with('success', 'Chart updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update chart', [
                'error' => $e->getMessage(),
                'chart_id' => $chart->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update chart. Please try again.');
        }
    }

    /**
     * Delete the specified chart.
     */
    public function delete(Chart $chart)
    {
        abort_unless($chart->sheet->spreadsheet->canEdit(auth()->id()), 403);

        try {
            $chart->delete();

            return redirect()->back()->with('success', 'Chart deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete chart', [
                'error' => $e->getMessage(),
                'chart_id' => $chart->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete chart. Please try again.');
        }
    }

    /**
     * List all charts on a sheet.
     */
    public function list(Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);

        try {
            $charts = $sheet->charts()->orderBy('created_at', 'desc')->get();

            return Inertia::render('Spreadsheets/Charts/List', [
                'charts' => $charts,
                'sheet' => $sheet,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list charts', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load charts. Please try again.');
        }
    }
}
