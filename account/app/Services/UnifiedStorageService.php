<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\DriveFile; // Assuming this model exists in account for global tracking

interface UnifiedStorageInterface
{
    public function upload(UploadedFile $file, string $service, array $options = []): array;
    public function download(string $fileId): mixed;
    public function delete(string $fileId): bool;
    public function getQuota(int $userId): array;
}

class UnifiedStorageService implements UnifiedStorageInterface
{
    /**
     * Upload a file to the unified storage
     */
    public function upload(UploadedFile $file, string $service, array $options = []): array
    {
        $userId = auth()->id();
        $path = $file->store("ecosystem/{$service}/{$userId}", 'local'); // Could be S3

        // Create a record in the unified drive_files table
        // This allows central quota management
        $record = \DB::table('drive_files')->insertGetId([
            'user_id' => $userId,
            'service' => $service,
            'name'    => $file->getClientOriginalName(),
            'path'    => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'metadata' => json_encode($options),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $record,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
        ];
    }

    public function download(string $fileId): mixed
    {
        $file = \DB::table('drive_files')->where('id', $fileId)->first();
        if (!$file) return null;

        return Storage::download($file->path, $file->name);
    }

    public function delete(string $fileId): bool
    {
        $file = \DB::table('drive_files')->where('id', $fileId)->first();
        if (!$file) return false;

        Storage::delete($file->path);
        return \DB::table('drive_files')->where('id', $fileId)->delete();
    }

    public function getQuota(int $userId): array
    {
        $used = \DB::table('drive_files')->where('user_id', $userId)->sum('size_bytes');
        $limit = 15 * 1024 * 1024 * 1024; // 15GB default

        return [
            'used' => $used,
            'limit' => $limit,
            'percentage' => ($used / $limit) * 100,
        ];
    }
}
