<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use App\Models\Sheet;
use App\Models\Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportController extends Controller
{
    /**
     * Route dispatcher — called by GET /spreadsheets/{id}/export/{format}.
     * Delegates to the appropriate typed export method.
     */
    public function export(Request $request, Spreadsheet $spreadsheet, string $format)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);

        return match (strtolower($format)) {
            'xlsx' => $this->exportXlsx($spreadsheet),
            'csv'  => $this->exportCsv($spreadsheet->defaultSheet ?? $spreadsheet->sheets()->firstOrFail()),
            'html' => $this->exportHtml($spreadsheet),
            'pdf'  => $this->exportPdf($request, $spreadsheet),
            default => abort(400, "Unsupported export format: {$format}. Supported: xlsx, csv, html, pdf"),
        };
    }

    /**
     * Export a spreadsheet to real .xlsx format using PhpSpreadsheet.
     * Includes all sheets, cells, formatting, and formulas.
     */
    public function exportXlsx(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);
        try {
            $spreadsheet->load(['sheets.cells']);

            $phpSpreadsheet = new PhpSpreadsheet();
            $phpSpreadsheet->removeSheetByIndex(0); // Remove default sheet

            foreach ($spreadsheet->sheets as $index => $sheet) {
                $phpSheet = $phpSpreadsheet->createSheet();
                $phpSheet->setTitle(substr($sheet->name, 0, 31)); // Excel max 31 chars

                // Build a lookup map of cells
                $cellsByAddress = [];
                foreach ($sheet->cells as $cell) {
                    $cellsByAddress[$cell->cell_address] = $cell;
                }

                // Write cell data
                foreach ($cellsByAddress as $address => $cell) {
                    $phpCell = $phpSheet->getCell($address);

                    // If it's a formula, set the formula
                    if ($cell->formula) {
                        $phpCell->setValue($cell->formula);
                    } else {
                        $phpCell->setValue($cell->value ?? '');
                    }

                    // Apply formatting if available
                    if ($cell->format_json) {
                        $this->applyCellFormatting($phpCell, $cell->format_json);
                    }
                }

                // Set column widths and row heights if configured
                if ($sheet->column_count > 0) {
                    for ($col = 1; $col <= $sheet->column_count; $col++) {
                        $columnLetter = Cell::getColumnLetter($col);
                        $phpSheet->getColumnDimension($columnLetter)->setWidth(12);
                    }
                }
            }

            // Generate filename
            $filename = $this->sanitizeFilename($spreadsheet->title) . '.xlsx';

            // Write to temp file and stream download
            $tempFile = storage_path('app/' . uniqid('export_') . '.xlsx');
            $writer = IOFactory::createWriter($phpSpreadsheet, 'Xlsx');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to export spreadsheet as XLSX', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to export as XLSX. Please try again.');
        }
    }

    /**
     * Export the active sheet as CSV.
     */
    public function exportCsv(Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);
        try {
            $sheet->load('cells');

            $cellsByAddress = [];
            foreach ($sheet->cells as $cell) {
                $cellsByAddress[$cell->cell_address] = $cell;
            }

            // Determine the used range
            if (empty($cellsByAddress)) {
                return redirect()->back()->with('error', 'Sheet is empty. Nothing to export.');
            }

            $maxRow = 0;
            $maxCol = 0;
            foreach ($cellsByAddress as $cell) {
                $maxRow = max($maxRow, $cell->row);
                $maxCol = max($maxCol, $cell->column);
            }

            // Generate CSV
            $filename = $this->sanitizeFilename($sheet->name) . '.csv';
            $tempFile = storage_path('app/' . uniqid('export_') . '.csv');

            $handle = fopen($tempFile, 'w');

            for ($row = 1; $row <= $maxRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $maxCol; $col++) {
                    $address = Cell::getColumnLetter($col) . $row;
                    $cell = $cellsByAddress[$address] ?? null;
                    $rowData[] = $cell?->computed_value ?? $cell?->value ?? '';
                }
                fputcsv($handle, $rowData);
            }

            fclose($handle);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to export sheet as CSV', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to export as CSV. Please try again.');
        }
    }

    /**
     * Export as PDF — requires the barryvdh/laravel-dompdf package.
     *
     * Install with: composer require barryvdh/laravel-dompdf
     * Until then returns 501 so callers get a clear error instead of
     * an HTML document incorrectly served as application/pdf.
     */
    public function exportPdf(Request $request, Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            abort(501, 'PDF export requires barryvdh/laravel-dompdf. Run: composer require barryvdh/laravel-dompdf');
        }

        try {
            $spreadsheet->load(['sheets.cells']);

            $html     = $this->generateHtmlExport($spreadsheet);
            $filename = $this->sanitizeFilename($spreadsheet->title) . '.pdf';
            $pdf      = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Failed to export spreadsheet as PDF', [
                'error'          => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to export as PDF. Please try again.');
        }
    }

    /**
     * Export the spreadsheet as a raw HTML table.
     */
    public function exportHtml(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);
        try {
            $spreadsheet->load(['sheets.cells']);

            $html = $this->generateHtmlExport($spreadsheet);

            $filename = $this->sanitizeFilename($spreadsheet->title) . '.html';

            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export spreadsheet as HTML', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to export as HTML. Please try again.');
        }
    }

    /**
     * Generate a complete HTML export of all sheets.
     */
    protected function generateHtmlExport(Spreadsheet $spreadsheet): string
    {
        $output = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($spreadsheet->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        h2 { color: #007bff; margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        td.formula { background-color: #fff3cd; }
        .meta { color: #666; font-size: 0.9em; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($spreadsheet->title) . '</h1>
    <div class="meta">
        <p>Exported: ' . now()->format('M d, Y H:i') . '</p>
        <p>Sheets: ' . $spreadsheet->sheets->count() . '</p>
    </div>';

        foreach ($spreadsheet->sheets as $sheet) {
            $output .= '<h2>' . htmlspecialchars($sheet->name) . '</h2>';
            $output .= $this->sheetToHtml($sheet);
        }

        $output .= '</body></html>';

        return $output;
    }

    /**
     * Convert a single sheet to an HTML table.
     */
    protected function sheetToHtml(Sheet $sheet): string
    {
        $cellsByAddress = [];
        foreach ($sheet->cells as $cell) {
            $cellsByAddress[$cell->cell_address] = $cell;
        }

        if (empty($cellsByAddress)) {
            return '<p><em>Empty sheet</em></p>';
        }

        $maxRow = 0;
        $maxCol = 0;
        foreach ($cellsByAddress as $cell) {
            $maxRow = max($maxRow, $cell->row);
            $maxCol = max($maxCol, $cell->column);
        }

        $html = '<table>';

        // Header row with column letters
        $html .= '<tr><th></th>';
        for ($col = 1; $col <= $maxCol; $col++) {
            $html .= '<th>' . Cell::getColumnLetter($col) . '</th>';
        }
        $html .= '</tr>';

        // Data rows
        for ($row = 1; $row <= $maxRow; $row++) {
            $html .= '<tr><th>' . $row . '</th>';
            for ($col = 1; $col <= $maxCol; $col++) {
                $address = Cell::getColumnLetter($col) . $row;
                $cell = $cellsByAddress[$address] ?? null;

                if ($cell) {
                    $value = htmlspecialchars((string) ($cell->computed_value ?? $cell->value ?? ''));
                    $class = $cell->formula ? ' class="formula"' : '';
                    $html .= '<td' . $class . '>' . $value . '</td>';
                } else {
                    $html .= '<td></td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Apply cell formatting from format_json to PhpSpreadsheet cell.
     */
    protected function applyCellFormatting(\PhpOffice\PhpSpreadsheet\Cell\Cell $phpCell, array $format): void
    {
        $style = [];

        // Font styling
        if (isset($format['bold'])) {
            $style['font']['bold'] = (bool) $format['bold'];
        }
        if (isset($format['italic'])) {
            $style['font']['italic'] = (bool) $format['italic'];
        }
        if (isset($format['underline'])) {
            $style['font']['underline'] = true;
        }
        if (isset($format['font_size'])) {
            $style['font']['size'] = (float) $format['font_size'];
        }
        if (isset($format['font_color'])) {
            $style['font']['color']['rgb'] = ltrim($format['font_color'], '#');
        }
        if (isset($format['font_family'])) {
            $style['font']['name'] = $format['font_family'];
        }

        // Fill/background color
        if (isset($format['background_color'])) {
            $style['fill']['fillType'] = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
            $style['fill']['startColor']['rgb'] = ltrim($format['background_color'], '#');
        }

        // Alignment
        if (isset($format['alignment'])) {
            $style['alignment']['horizontal'] = $format['alignment'];
        }
        if (isset($format['vertical_alignment'])) {
            $style['alignment']['vertical'] = $format['vertical_alignment'];
        }

        // Number format
        if (isset($format['number_format'])) {
            $style['numberFormat']['formatCode'] = $format['number_format'];
        }

        // Borders
        if (isset($format['border'])) {
            $style['borders']['allBorders']['borderStyle'] = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        }

        if (!empty($style)) {
            $phpCell->getStyle()->applyFromArray($style);
        }
    }

    /**
     * Sanitize filename for export.
     */
    protected function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}\s\._-]/u', '', $name);
        $name = preg_replace('/\s+/', '_', $name);

        return substr($name, 0, 100) ?: 'spreadsheet';
    }
}
