<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\FileActivity;
use App\Models\StorageQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $user     = Auth::user();
        $folderId = $request->query('folder');

        $files = DriveFile::where('user_id', $user->id)
            ->where('folder_id', $folderId)
            ->where('is_trashed', false)
            ->latest()
            ->get();

        $folders = DriveFolder::where('user_id', $user->id)
            ->where('parent_id', $folderId)
            ->where('is_trashed', false)
            ->orderBy('name')
            ->get();

        $currentFolder = $folderId ? DriveFolder::find($folderId) : null;
        $quota         = $user->storageQuota ?? StorageQuota::create(['user_id' => $user->id]);

        return response()->json([
            'files'          => $files,
            'folders'        => $folders,
            'current_folder' => $currentFolder,
            'quota' => [
                'used'       => $user->storage_used,
                'total'      => $quota->quota_bytes,
                'used_human' => $user->storage_used_for_humans,
                'total_human' => $quota->quota_for_humans,
                'percent'    => $quota->used_percent,
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'      => 'required|file|max:5120000',
            'folder_id' => 'nullable|exists:drive_folders,id',
        ]);

        $user      = Auth::user();
        $file      = $request->file('file');
        $quota     = $user->storageQuota ?? StorageQuota::create([
            'user_id'     => $user->id,
            'quota_bytes' => config('plans.personal.storage_bytes', 15 * 1024 ** 3),
            'used_bytes'  => 0,
        ]);

        if (!$quota->hasSpace($file->getSize())) {
            return response()->json([
                'error'       => 'Storage quota exceeded.',
                'used'        => $quota->used_for_humans,
                'total'       => $quota->quota_for_humans,
                'upgrade_url' => config('services.yg_account.url') . '/settings/billing/upgrade',
            ], 422);
        }

        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs("drive/{$user->id}", $filename, 'local');
        $checksum  = hash_file('sha256', $file->getRealPath());

        $driveFile = DriveFile::create([
            'user_id'       => $user->id,
            'folder_id'     => $request->folder_id,
            'name'          => $file->getClientOriginalName(),
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'disk'          => 'local',
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'extension'     => strtolower($file->getClientOriginalExtension()),
            'checksum'      => $checksum,
        ]);

        $quota->consume($file->getSize());
        $user->increment('storage_used', $file->getSize());

        FileActivity::create([
            'drive_file_id' => $driveFile->id,
            'user_id'       => $user->id,
            'action'        => 'upload',
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'created_at'    => now(),
        ]);

        return response()->json(['file' => $driveFile, 'message' => 'File uploaded successfully.']);
    }

    public function destroy(DriveFile $file)
    {
        abort_unless(Auth::id() === $file->user_id, 403);
        $file->update(['is_trashed' => true]);
        return response()->json(['message' => 'File moved to trash.']);
    }

    public function restore(DriveFile $file)
    {
        abort_unless(Auth::id() === $file->user_id, 403);
        $file->update(['is_trashed' => false]);
        return response()->json(['message' => 'File restored.']);
    }

    public function forceDelete(DriveFile $file)
    {
        abort_unless(Auth::id() === $file->user_id, 403);
        Storage::disk($file->disk)->delete($file->path);
        $user = Auth::user();
        $user->decrement('storage_used', $file->size);
        // Release bytes back to the quota record
        $quota = $user->storageQuota;
        if ($quota) {
            $quota->release($file->size);
        }
        $file->forceDelete();
        return response()->json(['message' => 'File permanently deleted.']);
    }

    public function star(DriveFile $file)
    {
        abort_unless(Auth::id() === $file->user_id, 403);
        $file->update(['is_starred' => !$file->is_starred]);
        return response()->json(['starred' => $file->is_starred]);
    }
}
