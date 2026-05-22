<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DownloadController extends Controller
{
    public function downloadDesktopApp(Request $request, $platform)
    {
        // Define available platforms and their corresponding file paths
        $availablePlatforms = [
            'windows' => 'YGXONE-Browser-Windows.exe',
            'macos' => 'YGXONE-Browser-macOS.dmg',
            'linux' => 'YGXONE-Browser-Linux.AppImage'
        ];

        // Validate the requested platform
        if (!isset($availablePlatforms[$platform])) {
            abort(404, 'Platform not available');
        }

        $fileName = $availablePlatforms[$platform];
        $filePath = storage_path("app/public/downloads/{$fileName}");

        // Check if file exists
        if (!File::exists($filePath)) {
            // Log the missing file for admin awareness
            \Log::warning("Desktop app download file not found: {$filePath}");
            
            // Return a response that informs the user about the missing file
            return response()->view('errors.download-missing', [
                'platform' => ucfirst($platform),
                'fileName' => $fileName
            ], 404);
        }

        // Increment download counter (optional)
        // You could add download statistics here if needed
        
        // Return the file download
        return response()->download($filePath, $fileName, [
            'Content-Type' => $this->getMimeType($platform)
        ]);
    }

    private function getMimeType($platform)
    {
        switch (strtolower($platform)) {
            case 'windows':
                return 'application/vnd.microsoft.portable-executable';
            case 'macos':
                return 'application/x-apple-diskimage';
            case 'linux':
                return 'application/x-executable';
            default:
                return 'application/octet-stream';
        }
    }

    public function showDownloadsPage()
    {
        return view('pages.downloads');
    }
}