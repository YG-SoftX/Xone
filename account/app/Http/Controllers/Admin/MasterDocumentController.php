<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class MasterDocumentController extends Controller
{
    private function getDocxUrl(): string
    {
        return config('services.yg_docx.url', env('VITE_YG_DOCX_URL', 'http://localhost:8003'));
    }

    private function getXcelUrl(): string
    {
        return config('services.yg_xcel.url', env('VITE_YG_XCEL_URL', 'http://localhost:8004'));
    }

    // ===== YG DocX Management =====

    public function documents(Request $request)
    {
        $docxUrl = $this->getDocxUrl();
        $params = $request->only(['search', 'type', 'status', 'user_id', 'page']);
        $response = Http::timeout(10)->get($docxUrl . '/api/admin/documents', $params);
        $data = $response->json() ?? [];

        return view('admin.master.documents.index', [
            'documents' => $data['data'] ?? [],
            'stats' => $data['stats'] ?? [],
        ]);
    }

    public function showDocument($id)
    {
        $docxUrl = $this->getDocxUrl();
        $document = $this->callApi($docxUrl, "/api/admin/documents/{$id}");

        return view('admin.master.documents.show', compact('document'));
    }

    public function deleteDocument($id)
    {
        $docxUrl = $this->getDocxUrl();
        Http::timeout(10)->delete($docxUrl . "/api/admin/documents/{$id}");

        return redirect()->back()->with('success', 'Document deleted.');
    }

    public function documentVersions($id)
    {
        $docxUrl = $this->getDocxUrl();
        $versions = $this->callApi($docxUrl, "/api/admin/documents/{$id}/versions");
        $documentId = $id;

        return view('admin.master.documents.versions', compact('versions', 'documentId'));
    }

    // ===== YG Xcel Management =====

    public function spreadsheets(Request $request)
    {
        $xcelUrl = $this->getXcelUrl();
        $params = $request->only(['search', 'status', 'user_id', 'page']);
        $response = Http::timeout(10)->get($xcelUrl . '/api/admin/spreadsheets', $params);
        $data = $response->json() ?? [];

        return view('admin.master.spreadsheets.index', [
            'spreadsheets' => $data['data'] ?? [],
            'stats' => $data['stats'] ?? [],
        ]);
    }

    public function showSpreadsheet($id)
    {
        $xcelUrl = $this->getXcelUrl();
        $spreadsheet = $this->callApi($xcelUrl, "/api/admin/spreadsheets/{$id}");

        return view('admin.master.spreadsheets.show', compact('spreadsheet'));
    }

    public function deleteSpreadsheet($id)
    {
        $xcelUrl = $this->getXcelUrl();
        Http::timeout(10)->delete($xcelUrl . "/api/admin/spreadsheets/{$id}");

        return redirect()->back()->with('success', 'Spreadsheet deleted.');
    }

    // ===== Template Management (Both Services) =====

    public function templates(Request $request)
    {
        $docxUrl = $this->getDocxUrl();
        $xcelUrl = $this->getXcelUrl();

        $docxTemplates = $this->callApi($docxUrl, '/api/admin/templates');
        $xcelTemplates = $this->callApi($xcelUrl, '/api/admin/templates');

        return view('admin.master.templates.index', [
            'docxTemplates' => $docxTemplates['data'] ?? [],
            'xcelTemplates' => $xcelTemplates['data'] ?? [],
        ]);
    }

    /**
     * Helper: Call service API
     */
    private function callApi(string $baseUrl, string $endpoint, string $method = 'GET', array $data = []): array
    {
        try {
            if ($method === 'GET') {
                $response = Http::timeout(10)->get($baseUrl . $endpoint);
            } else {
                $response = Http::timeout(10)->post($baseUrl . $endpoint, $data);
            }
            if ($response->successful()) {
                return $response->json() ?? ['success' => true];
            }
            return ['error' => 'Request failed', 'status' => $response->status()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
