<?php

namespace App\Modules\Xcel\Controllers;

use App\Http\Controllers\Controller;
use App\Models\XcelWorkbook;
use App\Models\XcelSheet;
use App\Models\XcelCell;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XcelController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display workbooks dashboard
     */
    public function index()
    {
        $workbooks = XcelWorkbook::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_workbooks' => XcelWorkbook::where('user_id', auth()->id())->count(),
            'total_sheets' => XcelSheet::whereIn('workbook_id', 
                XcelWorkbook::where('user_id', auth()->id())->pluck('id')
            )->count(),
            'total_cells' => XcelCell::whereIn('sheet_id',
                XcelSheet::whereIn('workbook_id',
                    XcelWorkbook::where('user_id', auth()->id())->pluck('id')
                )->pluck('id')
            )->count(),
        ];

        return view('xcel.index', compact('workbooks', 'stats'));
    }

    /**
     * Create new workbook
     */
    public function create()
    {
        return view('xcel.create');
    }

    /**
     * Store new workbook
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:xcel_templates,id',
        ]);

        $workbook = XcelWorkbook::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'uuid' => Str::uuid()->toString(),
            'theme' => json_encode([
                'primary_color' => '#0f9d58', // Google Sheets green
                'font_family' => 'Arial, sans-serif',
            ]),
            'settings' => json_encode([
                'default_row_count' => 100,
                'default_column_count' => 26,
            ]),
        ]);

        // Create default sheet
        $sheet = $workbook->sheets()->create([
            'name' => 'Sheet1',
            'order' => 0,
            'row_count' => 100,
            'column_count' => 26,
        ]);

        // Load template if provided
        if ($validated['template_id']) {
            $template = \App\Models\XcelTemplate::findOrFail($validated['template_id']);
            // TODO: Load template structure
        }

        // Publish event
        $this->eventService->publish(
            'xcel',
            'workbook_created',
            [
                'workbook_id' => $workbook->id,
                'title' => $workbook->title,
            ],
            auth()->id()
        );

        return redirect()->route('xcel.edit', $workbook->id);
    }

    /**
     * Edit workbook (spreadsheet interface)
     */
    public function edit($workbookId)
    {
        $workbook = XcelWorkbook::findOrFail($workbookId);
        
        // Verify ownership or collaboration permission
        $isOwner = $workbook->user_id === auth()->id();
        $isCollaborator = $workbook->collaborators()
            ->where('user_id', auth()->id())
            ->whereIn('role', ['owner', 'editor'])
            ->exists();
        
        abort_unless($isOwner || $isCollaborator, 403);

        $sheets = $workbook->sheets()->orderBy('order')->get();
        $firstSheet = $sheets->first();

        // Load cells for first sheet (limit to visible range for performance)
        $cells = [];
        if ($firstSheet) {
            $cells = $firstSheet->cells()
                ->where('row', '<=', 50) // Load first 50 rows initially
                ->where('column', '<=', 26) // Load first 26 columns (A-Z)
                ->get()
                ->groupBy('row');
        }

        return view('xcel.edit', compact('workbook', 'sheets', 'firstSheet', 'cells'));
    }

    /**
     * Update workbook settings
     */
    public function update(Request $request, $workbookId)
    {
        $workbook = XcelWorkbook::findOrFail($workbookId);
        abort_unless($workbook->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'theme' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $workbook->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Add new sheet
     */
    public function addSheet(Request $request, $workbookId)
    {
        $workbook = XcelWorkbook::findOrFail($workbookId);
        abort_unless($workbook->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = $workbook->sheets()->max('order') ?? -1;

        $sheet = $workbook->sheets()->create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
            'row_count' => 100,
            'column_count' => 26,
        ]);

        $workbook->increment('sheet_count');

        return response()->json(['sheet' => $sheet]);
    }

    /**
     * Update cell value
     */
    public function updateCell(Request $request, $workbookId, $sheetId, $cellId)
    {
        $cell = XcelCell::findOrFail($cellId);
        abort_unless($cell->sheet->workbook->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'value' => 'nullable|string',
            'formula' => 'nullable|string',
            'format' => 'nullable|array',
        ]);

        // If formula, calculate result
        $result = null;
        $dataType = 'text';
        
        if (!empty($validated['formula'])) {
            $result = $this->calculateFormula($validated['formula'], $cell->sheet);
            $dataType = 'formula';
        } elseif (!empty($validated['value'])) {
            $result = $validated['value'];
            $dataType = $this->detectDataType($validated['value']);
        }

        $cell->update([
            'value' => $validated['value'],
            'display_value' => $result,
            'formula' => $validated['formula'],
            'data_type' => $dataType,
            'format' => $validated['format'],
            'last_modified_at' => now(),
        ]);

        // Update dependent cells if formula changed
        if (!empty($validated['formula'])) {
            $this->updateDependentCells($cell);
        }

        return response()->json([
            'success' => true,
            'result' => $result,
            'data_type' => $dataType,
        ]);
    }

    /**
     * Batch update cells (for paste operations)
     */
    public function batchUpdateCells(Request $request, $workbookId, $sheetId)
    {
        $sheet = XcelSheet::findOrFail($sheetId);
        abort_unless($sheet->workbook->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'cells' => 'required|array',
            'cells.*.row' => 'required|integer|min:1',
            'cells.*.column' => 'required|integer|min:1',
            'cells.*.value' => 'nullable|string',
            'cells.*.formula' => 'nullable|string',
        ]);

        $updatedCells = [];
        
        foreach ($validated['cells'] as $cellData) {
            $cellReference = $this->getCellReference($cellData['row'], $cellData['column']);
            
            $cell = $sheet->cells()->updateOrCreate(
                [
                    'row' => $cellData['row'],
                    'column' => $cellData['column'],
                ],
                [
                    'cell_reference' => $cellReference,
                    'value' => $cellData['value'],
                    'formula' => $cellData['formula'],
                    'last_modified_at' => now(),
                ]
            );

            // Calculate if formula
            if (!empty($cellData['formula'])) {
                $result = $this->calculateFormula($cellData['formula'], $sheet);
                $cell->update([
                    'display_value' => $result,
                    'data_type' => 'formula',
                ]);
            }

            $updatedCells[] = $cell;
        }

        return response()->json(['cells' => $updatedCells]);
    }

    /**
     * Get cell data for range
     */
    public function getCellRange(Request $request, $workbookId, $sheetId)
    {
        $sheet = XcelSheet::findOrFail($sheetId);
        
        $validated = $request->validate([
            'start_row' => 'required|integer|min:1',
            'start_column' => 'required|integer|min:1',
            'end_row' => 'required|integer|min:1',
            'end_column' => 'required|integer|min:1',
        ]);

        $cells = $sheet->cells()
            ->where('row', '>=', $validated['start_row'])
            ->where('row', '<=', $validated['end_row'])
            ->where('column', '>=', $validated['start_column'])
            ->where('column', '<=', $validated['end_column'])
            ->get();

        return response()->json(['cells' => $cells]);
    }

    /**
     * Add chart
     */
    public function addChart(Request $request, $workbookId, $sheetId)
    {
        $sheet = XcelSheet::findOrFail($sheetId);
        abort_unless($sheet->workbook->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:bar,column,line,area,pie,doughnut,scatter,bubble,radar,combo',
            'data_range' => 'required|array',
            'position' => 'required|array',
        ]);

        $chart = $sheet->charts()->create($validated);

        return response()->json(['chart' => $chart]);
    }

    /**
     * Share workbook with collaborator
     */
    public function share(Request $request, $workbookId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:editor,commenter,viewer',
        ]);

        $workbook = XcelWorkbook::findOrFail($workbookId);
        abort_unless($workbook->user_id === auth()->id(), 403);

        $user = \App\Models\User::where('email', $validated['email'])->first();
        
        if ($user) {
            $workbook->collaborators()->create([
                'user_id' => $user->id,
                'role' => $validated['role'],
            ]);
        }

        return redirect()->back()->with('success', 'Workbook shared successfully!');
    }

    /**
     * Export workbook
     */
    public function export($workbookId, $format = 'csv')
    {
        $workbook = XcelWorkbook::findOrFail($workbookId);
        abort_unless($workbook->user_id === auth()->id(), 403);

        if ($format === 'csv') {
            return $this->exportToCSV($workbook);
        } elseif ($format === 'xlsx') {
            // TODO: Implement XLSX export using PhpSpreadsheet
            abort(501, 'XLSX export not yet implemented');
        }

        abort(400, 'Unsupported format');
    }

    /**
     * Delete workbook
     */
    public function destroy($workbookId)
    {
        $workbook = XcelWorkbook::findOrFail($workbookId);
        abort_unless($workbook->user_id === auth()->id(), 403);

        $workbook->delete();

        return redirect()->route('xcel.index')->with('success', 'Workbook deleted');
    }

    /**
     * Calculate formula result
     */
    protected function calculateFormula(string $formula, XcelSheet $sheet)
    {
        // Remove leading =
        $formula = ltrim($formula, '=');
        
        try {
            // Basic formula evaluation (expand this for production)
            return $this->evaluateFormula($formula, $sheet);
        } catch (\Exception $e) {
            return '#ERROR: ' . $e->getMessage();
        }
    }

    /**
     * Evaluate formula expression.
     *
     * IMPORTANT: eval() must never be used on user-supplied formulas — it is RCE.
     * Arithmetic is evaluated via a safe recursive descent parser instead.
     */
    protected function evaluateFormula(string $formula, XcelSheet $sheet)
    {
        // Handle SUM function
        if (preg_match('/^SUM\(([A-Z]+)(\d+):([A-Z]+)(\d+)\)$/i', $formula, $matches)) {
            return $this->calculateSum($matches[1], (int) $matches[2], $matches[3], (int) $matches[4], $sheet);
        }

        // Handle AVERAGE function
        if (preg_match('/^AVERAGE\(([A-Z]+)(\d+):([A-Z]+)(\d+)\)$/i', $formula, $matches)) {
            return $this->calculateAverage($matches[1], (int) $matches[2], $matches[3], (int) $matches[4], $sheet);
        }

        // Handle basic arithmetic — safe whitelist parser (no eval)
        if (preg_match('/^[0-9+\-*\/().\s]+$/', $formula)) {
            return $this->safeEval($formula);
        }

        return $formula;
    }

    /**
     * Evaluate a simple arithmetic expression without eval().
     * Supports +, -, *, / and parentheses over integer/float literals.
     */
    protected function safeEval(string $expr): float|int|string
    {
        $expr = trim($expr);

        // Strip outer parentheses
        while (str_starts_with($expr, '(') && str_ends_with($expr, ')')) {
            $inner = substr($expr, 1, -1);
            if ($this->isBalanced($inner)) {
                $expr = trim($inner);
            } else {
                break;
            }
        }

        // Try addition / subtraction (lowest precedence, right-to-left scan)
        $depth = 0;
        for ($i = strlen($expr) - 1; $i >= 0; $i--) {
            $c = $expr[$i];
            if ($c === ')') $depth++;
            if ($c === '(') $depth--;
            if ($depth === 0 && ($c === '+' || $c === '-') && $i > 0) {
                $left  = $this->safeEval(substr($expr, 0, $i));
                $right = $this->safeEval(substr($expr, $i + 1));
                return $c === '+' ? $left + $right : $left - $right;
            }
        }

        // Try multiplication / division
        $depth = 0;
        for ($i = strlen($expr) - 1; $i >= 0; $i--) {
            $c = $expr[$i];
            if ($c === ')') $depth++;
            if ($c === '(') $depth--;
            if ($depth === 0 && ($c === '*' || $c === '/')) {
                $left  = $this->safeEval(substr($expr, 0, $i));
                $right = $this->safeEval(substr($expr, $i + 1));
                if ($c === '/' && (float) $right == 0.0) {
                    return '#DIV/0!';
                }
                return $c === '*' ? $left * $right : $left / $right;
            }
        }

        // Base case: numeric literal
        if (is_numeric($expr)) {
            return str_contains($expr, '.') ? (float) $expr : (int) $expr;
        }

        return '#VALUE!';
    }

    /** Check if parentheses in $expr are balanced. */
    private function isBalanced(string $expr): bool
    {
        $depth = 0;
        foreach (str_split($expr) as $c) {
            if ($c === '(') $depth++;
            if ($c === ')') $depth--;
            if ($depth < 0) return false;
        }
        return $depth === 0;
    }

    /**
     * Calculate SUM of range
     */
    protected function calculateSum(string $startCol, int $startRow, string $endCol, int $endRow, XcelSheet $sheet)
    {
        $sum = 0;
        $startColNum = $this->columnLetterToNumber($startCol);
        $endColNum = $this->columnLetterToNumber($endCol);

        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = $startColNum; $col <= $endColNum; $col++) {
                $cell = $sheet->cells()
                    ->where('row', $row)
                    ->where('column', $col)
                    ->first();
                
                if ($cell && is_numeric($cell->value)) {
                    $sum += floatval($cell->value);
                }
            }
        }

        return $sum;
    }

    /**
     * Calculate AVERAGE of range
     */
    protected function calculateAverage(string $startCol, int $startRow, string $endCol, int $endRow, XcelSheet $sheet)
    {
        $sum = $this->calculateSum($startCol, $startRow, $endCol, $endRow, $sheet);
        $count = ($endRow - $startRow + 1) * ($this->columnLetterToNumber($endCol) - $this->columnLetterToNumber($startCol) + 1);
        
        return $count > 0 ? $sum / $count : 0;
    }

    /**
     * Convert column letter to number (A=1, B=2, etc.)
     */
    protected function columnLetterToNumber(string $letter): int
    {
        $letter = strtoupper($letter);
        $length = strlen($letter);
        $number = 0;
        
        for ($i = 0; $i < $length; $i++) {
            $number = $number * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        
        return $number;
    }

    /**
     * Convert column number to letter (1=A, 2=B, etc.)
     */
    protected function columnNumberToLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $number = intval(($number - $remainder) / 26);
        }
        return $letter;
    }

    /**
     * Get cell reference from row and column
     */
    protected function getCellReference(int $row, int $column): string
    {
        return $this->columnNumberToLetter($column) . $row;
    }

    /**
     * Detect data type from value
     */
    protected function detectDataType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_numeric($value)) return 'number';
        if (strtotime($value)) return 'date';
        return 'text';
    }

    /**
     * Update dependent cells when formula changes
     */
    protected function updateDependentCells(XcelCell $cell)
    {
        // TODO: Implement dependency tracking and recalculation
        // This requires building a dependency graph
    }

    /**
     * Export to CSV
     */
    protected function exportToCSV(XcelWorkbook $workbook)
    {
        $sheet = $workbook->sheets()->first();
        $cells = $sheet->cells()->orderBy('row')->orderBy('column')->get();
        
        $csv = fopen('php://temp', 'r+');
        
        $currentRow = 1;
        $rowData = [];
        
        foreach ($cells as $cell) {
            if ($cell->row != $currentRow) {
                fputcsv($csv, $rowData);
                $rowData = [];
                $currentRow = $cell->row;
            }
            $rowData[$cell->column] = $cell->display_value ?? $cell->value;
        }
        
        if (!empty($rowData)) {
            fputcsv($csv, $rowData);
        }
        
        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);

        return response($output)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$workbook->title}.csv\"");
    }
}
