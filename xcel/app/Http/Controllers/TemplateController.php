<?php

namespace App\Http\Controllers;

use App\Models\Spreadsheet;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TemplateController extends Controller
{
    /**
     * List all templates with optional category filter.
     */
    public function index(Request $request)
    {
        try {
            $query = Template::query();

            // Show public and system templates by default
            $query->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('is_system', true);
            });

            // Include user's own templates
            if ($request->user()) {
                $query->orWhere('user_id', $request->user()->id);
            }

            // Category filter
            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $templates = $query->latest()->get();

            // Group by category
            $groupedTemplates = $templates->groupBy('category');

            return Inertia::render('Spreadsheets/Templates/Index', [
                'templates' => $templates,
                'groupedTemplates' => $groupedTemplates,
                'categories' => $templates->pluck('category')->unique()->filter()->sort()->values(),
                'filters' => $request->only(['category', 'search']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list templates', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to load templates. Please try again.');
        }
    }

    /**
     * Save the current spreadsheet as a template.
     */
    public function store(Request $request, Spreadsheet $spreadsheet)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'nullable|string|max:100',
                'is_public' => 'nullable|boolean',
            ]);

            // Load the spreadsheet with all sheets and cells
            $spreadsheet->load(['sheets.cells']);

            // Build sheets_data from the spreadsheet
            $sheetsData = [];
            foreach ($spreadsheet->sheets as $sheet) {
                $sheetData = [
                    'name' => $sheet->name,
                    'order_index' => $sheet->order_index,
                    'row_count' => $sheet->row_count,
                    'column_count' => $sheet->column_count,
                    'cells' => [],
                ];

                foreach ($sheet->cells as $cell) {
                    $sheetData['cells'][] = [
                        'cell_address' => $cell->cell_address,
                        'row' => $cell->row,
                        'column' => $cell->column,
                        'value' => $cell->value,
                        'formula' => $cell->formula,
                        'computed_value' => $cell->computed_value,
                        'data_type' => $cell->data_type,
                        'format_json' => $cell->format_json,
                    ];
                }

                $sheetsData[] = $sheetData;
            }

            $template = Template::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? 'custom',
                'is_public' => $validated['is_public'] ?? false,
                'is_system' => false,
                'sheets_data' => $sheetsData,
            ]);

            return redirect()->back()->with('success', 'Template saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to save template', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to save template. Please try again.');
        }
    }

    /**
     * Create a new spreadsheet from a template.
     */
    public function apply(Request $request, Template $template)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            // Create the spreadsheet
            $spreadsheet = Spreadsheet::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => 'draft',
            ]);

            // Apply template sheets and cells
            $sheetsData = $template->sheets_data;

            if (is_array($sheetsData)) {
                foreach ($sheetsData as $index => $sheetData) {
                    $sheet = $spreadsheet->sheets()->create([
                        'name' => $sheetData['name'] ?? 'Sheet ' . ($index + 1),
                        'order_index' => $index,
                        'row_count' => $sheetData['row_count'] ?? 1000,
                        'column_count' => $sheetData['column_count'] ?? 26,
                    ]);

                    // Set first sheet as default
                    if ($index === 0) {
                        $spreadsheet->update(['default_sheet_id' => $sheet->id]);
                    }

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

            return redirect()->route('spreadsheets.show', $spreadsheet)
                ->with('success', 'Spreadsheet created from template successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to apply template', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);

            return redirect()->back()->with('error', 'Failed to create spreadsheet from template. Please try again.');
        }
    }

    /**
     * Delete a template.
     */
    public function delete(Template $template)
    {
        try {
            // Only allow deletion by owner or if not a system template
            if ($template->is_system) {
                return redirect()->back()->with('error', 'Cannot delete system templates.');
            }

            if ($template->user_id !== auth()->id()) {
                return redirect()->back()->with('error', 'You do not have permission to delete this template.');
            }

            $template->delete();

            return redirect()->back()->with('success', 'Template deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete template', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete template. Please try again.');
        }
    }
}
