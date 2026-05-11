<?php

namespace App\Http\Controllers;

use App\Models\Spreadsheet;
use App\Models\Sheet;
use App\Models\SpreadsheetActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SpreadsheetController extends Controller
{
    /**
     * Display a listing of spreadsheets with search, filter, status, and pagination.
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $query = Spreadsheet::with(['user', 'defaultSheet'])
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhereHas('shares', function ($sub) use ($userId, $request) {
                            $sub->where('user_id', $userId)
                                ->orWhere('email', $request->user()->email);
                        });
                })
                ->latest();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('title', 'like', "%{$search}%");
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Folder filter
            if ($request->filled('folder_id')) {
                $query->where('folder_id', $request->input('folder_id'));
            }

            $spreadsheets = $query->paginate(20)->withQueryString();

            return Inertia::render('Spreadsheets/Index', [
                'spreadsheets' => $spreadsheets,
                'filters' => $request->only(['search', 'status', 'folder_id']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list spreadsheets', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to load spreadsheets. Please try again.');
        }
    }

    /**
     * Display the specified spreadsheet with sheets, cells, charts.
     */
    public function show(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);

        try {
            $spreadsheet->load([
                'sheets.cells',
                'sheets.charts',
                'shares.user',
                'shares.createdBy',
            ]);

            return Inertia::render('Spreadsheets/Show', [
                'spreadsheet' => $spreadsheet,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load spreadsheet. Please try again.');
        }
    }

    /**
     * Show the form for creating a new spreadsheet.
     */
    public function create(Request $request)
    {
        try {
            return Inertia::render('Spreadsheets/Create');
        } catch (\Exception $e) {
            Log::error('Failed to load spreadsheet creation form', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to load creation form.');
        }
    }

    /**
     * Store a newly created spreadsheet with a default Sheet 1.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'folder_id' => 'nullable|integer',
            ]);

            $spreadsheet = Spreadsheet::create([
                'user_id' => $request->user()->id,
                'folder_id' => $validated['folder_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => 'draft',
            ]);

            // Auto-create Sheet 1
            $sheet = Sheet::create([
                'spreadsheet_id' => $spreadsheet->id,
                'name' => 'Sheet 1',
                'order_index' => 0,
                'row_count' => 1000,
                'column_count' => 26,
            ]);

            $spreadsheet->update(['default_sheet_id' => $sheet->id]);

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $request->user()->id,
                'created',
                ['title' => $spreadsheet->title]
            );

            return redirect()->route('spreadsheets.show', $spreadsheet)
                ->with('success', 'Spreadsheet created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create spreadsheet', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to create spreadsheet. Please try again.')->withInput();
        }
    }

    /**
     * Update the specified spreadsheet (rename, description).
     */
    public function update(Request $request, Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,active,archived',
            ]);

            $spreadsheet->update($validated);

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $request->user()->id,
                'updated',
                ['fields' => array_keys($validated)]
            );

            return redirect()->back()->with('success', 'Spreadsheet updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update spreadsheet. Please try again.');
        }
    }

    /**
     * Duplicate the spreadsheet with all sheets, cells, and charts.
     */
    public function duplicate(Request $request, Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);

        try {
            $duplicate = $spreadsheet->duplicate();
            $duplicate->user_id = $request->user()->id;
            $duplicate->save();

            SpreadsheetActivity::recordActivity(
                $duplicate->id,
                $request->user()->id,
                'duplicated',
                ['source_id' => $spreadsheet->id]
            );

            return redirect()->route('spreadsheets.show', $duplicate)
                ->with('success', 'Spreadsheet duplicated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to duplicate spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to duplicate spreadsheet. Please try again.');
        }
    }

    /**
     * Archive the specified spreadsheet.
     */
    public function archive(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->user_id === auth()->id(), 403);

        try {
            $spreadsheet->update(['status' => 'archived']);

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $spreadsheet->user_id,
                'archived'
            );

            return redirect()->back()->with('success', 'Spreadsheet archived successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to archive spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to archive spreadsheet. Please try again.');
        }
    }

    /**
     * Restore the archived spreadsheet.
     */
    public function restore(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->user_id === auth()->id(), 403);

        try {
            $spreadsheet->update(['status' => 'active']);

            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $spreadsheet->user_id,
                'restored'
            );

            return redirect()->back()->with('success', 'Spreadsheet restored successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to restore spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to restore spreadsheet. Please try again.');
        }
    }

    /**
     * Soft delete the specified spreadsheet.
     */
    public function delete(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->user_id === auth()->id(), 403);

        try {
            SpreadsheetActivity::recordActivity(
                $spreadsheet->id,
                $spreadsheet->user_id,
                'deleted'
            );

            $spreadsheet->delete();

            return redirect()->route('spreadsheets.index')
                ->with('success', 'Spreadsheet deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete spreadsheet. Please try again.');
        }
    }

    /**
     * Imperial Revenue API
     * Provides real-time data for sovereign formulas like =YG_REVENUE_TODAY()
     */
    public function imperialRevenue(Request $request)
    {
        // In a full multi-node setup, this would ping the YG Pay/AdSense node.
        // For now, we simulate the Sovereign ecosystem response.
        $formula = $request->query('formula');

        $data = match ($formula) {
            'YG_REVENUE_TODAY' => rand(150, 500) . '.00',
            'YG_REVENUE_MONTH' => rand(5000, 15000) . '.00',
            'YG_AD_IMPRESSIONS' => rand(10000, 50000),
            'YG_ACTIVE_USERS' => rand(1000, 5000),
            default => '0.00',
        };

        return response()->json([
            'formula' => $formula,
            'value'   => $data,
            'currency' => 'USD',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
