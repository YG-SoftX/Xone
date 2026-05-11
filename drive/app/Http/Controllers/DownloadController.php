<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\FileActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function download(Request $request, DriveFile $file)
    {
        $user = Auth::user();

        $canAccess = $file->user_id === $user?->id
            || $file->shares()->where('shared_with_id', $user?->id)->exists()
            || $file->shares()->where('shared_with_email', $user?->email)->exists();

        if (!$canAccess && !$file->shared_link) {
            abort(403, 'Access denied.');
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'File not found on disk.');
        }

        $file->increment('download_count');

        FileActivity::create([
            'drive_file_id' => $file->id,
            'user_id'       => $user?->id,
            'action'        => 'download',
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'created_at'    => now(),
        ]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function sharedLink(Request $request, string $token)
    {
        $file = DriveFile::where('shared_link', $token)->firstOrFail();

        if ($file->shared_link_expires_at && $file->shared_link_expires_at->isPast()) {
            abort(410, 'This share link has expired.');
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'File not found.');
        }

        $file->increment('download_count');

        FileActivity::create([
            'drive_file_id' => $file->id,
            'user_id'       => null,
            'action'        => 'shared_link_download',
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'created_at'    => now(),
        ]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
