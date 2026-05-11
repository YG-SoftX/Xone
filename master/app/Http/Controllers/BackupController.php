<?php

namespace App\Http\Controllers;

use App\Jobs\BackupDatabaseJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BackupController
 * 
 * Manages database backup operations (create, list, restore, delete).
 */
class BackupController extends Controller
{
    /**
     * Trigger a new backup.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'service' => 'nullable|string', // Optional: specific service slug
        ]);

        try {
            dispatch(new BackupDatabaseJob($validated['service'] ?? null));
            
            return response()->json([
                'success' => true,
                'message' => 'Backup initiated successfully',
                'service' => $validated['service'] ?? 'all',
            ]);

        } catch (\Exception $e) {
            Log::error('Backup initiation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available backups.
     */
    public function list()
    {
        try {
            $backupPath = storage_path('app/backups');
            
            if (!File::exists($backupPath)) {
                return response()->json([
                    'success' => true,
                    'backups' => [],
                    'total_count' => 0,
                ]);
            }

            $backups = collect(File::files($backupPath))
                ->map(function ($file) {
                    return [
                        'filename' => $file->getFilename(),
                        'size' => $this->formatFileSize($file->getSize()),
                        'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                        'path' => $file->getPathname(),
                    ];
                })
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'total_count' => $backups->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download($filename)
    {
        try {
            $filepath = storage_path("app/backups/{$filename}");
            
            if (!File::exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ], 404);
            }

            return response()->download($filepath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup file.
     */
    public function delete($filename)
    {
        try {
            $filepath = storage_path("app/backups/{$filename}");
            
            if (!File::exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ], 404);
            }

            File::delete($filepath);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format file size to human-readable format.
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
