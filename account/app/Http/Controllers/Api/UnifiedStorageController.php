<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UnifiedStorageService;
use Illuminate\Http\Request;

class UnifiedStorageController extends Controller
{
    public function __construct(protected UnifiedStorageService $storageService)
    {
    }

    /**
     * Upload a file from any ecosystem module
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'service' => 'nullable|string',
            'folder' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $service = $request->input('service', 'unknown');
        $folder = $request->input('folder', 'uploads');

        $result = $this->storageService->uploadFile(
            $file,
            "{$service}/{$folder}"
        );

        return response()->json([
            'status' => 'success',
            'file' => $result,
        ]);
    }

    /**
     * Get storage stats for the user
     */
    public function stats()
    {
        return response()->json([
            'status' => 'success',
            'stats' => $this->storageService->getStorageStats(auth()->id()),
        ]);
    }
}
