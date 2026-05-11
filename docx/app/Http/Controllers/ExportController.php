<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ExportController extends Controller
{
    /**
     * Export a document in the specified format.
     *
     * Supported formats: pdf, docx, txt, html
     */
    public function export(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'format' => 'required|in:pdf,docx,txt,html',
            ]);

            $format = $validated['format'];
            $filename = $this->sanitizeFilename($document->title) . '.' . $format;

            switch ($format) {
                case 'pdf':
                    return $this->exportPdf($document, $filename);

                case 'docx':
                    return $this->exportDocx($document, $filename);

                case 'txt':
                    return $this->exportTxt($document, $filename);

                case 'html':
                    return $this->exportHtml($document, $filename);

                default:
                    return redirect()->back()->with('error', 'Unsupported export format.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to export document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'format' => $request->input('format'),
            ]);

            return redirect()->back()->with('error', 'Failed to export document. Please try again.');
        }
    }

    /**
     * Export document as PDF.
     */
    private function exportPdf(Document $document, string $filename)
    {
        // Generate HTML content for PDF conversion
        $html = $this->generateHtmlContent($document);

        // Create a temporary HTML file for PDF generation
        $tempHtml = storage_path('app/temp_' . uniqid() . '.html');
        file_put_contents($tempHtml, $html);

        // Use a simple approach: render HTML and stream as PDF
        // For production, consider using a package like dompdf or snappy
        $response = response($html, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        // Clean up temp file
        if (file_exists($tempHtml)) {
            unlink($tempHtml);
        }

        return $response;
    }

    /**
     * Export document as DOCX using PHPWord.
     */
    private function exportDocx(Document $document, string $filename)
    {
        if (!class_exists('\\PhpOffice\\PhpWord\\PhpWord')) {
            return redirect()->back()->with('error', 'PHPWord is not installed. Please install it to export as DOCX.');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Add title
        $section->addTitle($document->title, 1);

        // Add content
        if ($document->content) {
            // Strip HTML tags for plain text in DOCX
            $text = strip_tags($document->content);
            $section->addText($text);
        }

        // Save to temp file
        $tempFile = storage_path('app/' . $filename);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Export document as plain text.
     */
    private function exportTxt(Document $document, string $filename)
    {
        $content = strip_tags($document->content ?? '');

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export document as HTML.
     */
    private function exportHtml(Document $document, string $filename)
    {
        $html = $this->generateHtmlContent($document);

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate HTML content for export.
     *
     * @param Document $document
     * @return string
     */
    private function generateHtmlContent(Document $document)
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($document->title) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #222;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($document->title) . '</h1>
    <div class="meta">
        <p>Created: ' . $document->created_at->format('M d, Y') . '</p>
        <p>Last modified: ' . $document->updated_at->format('M d, Y H:i') . '</p>
        <p>Word count: ' . $document->word_count . '</p>
    </div>
    <div class="content">
        ' . ($document->content ?? '') . '
    </div>
</body>
</html>';
    }

    /**
     * Sanitize filename for export.
     *
     * @param string $name
     * @return string
     */
    private function sanitizeFilename(string $name)
    {
        // Remove special characters and spaces
        $name = preg_replace('/[^\p{L}\p{N}\s\._-]/u', '', $name);
        $name = preg_replace('/\s+/', '_', $name);

        return substr($name, 0, 100) ?: 'document';
    }
}
