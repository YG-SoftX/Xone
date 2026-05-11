<?php

namespace App\Modules\Drive\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display drive home
     */
    public function index(Request $request)
    {
        $folderId = $request->input('folder');
        
        if ($folderId) {
            $folder = DriveFolder::findOrFail($folderId);
            abort_unless($folder->user_id === auth()->id(), 403);
            
            $folders = DriveFolder::where('user_id', auth()->id())
                ->where('parent_id', $folderId)
                ->orderBy('name')
                ->get();
                
            $files = DriveFile::where('user_id', auth()->id())
                ->where('folder_id', $folderId)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $folder = null;
            $folders = DriveFolder::where('user_id', auth()->id())
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
                
            $files = DriveFile::where('user_id', auth()->id())
                ->whereNull('folder_id')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $storageUsed = auth()->user()->driveFiles()->sum('size_bytes');
        $storageQuota = 10737418240; // 10GB default

        return view('drive.index', compact('folder', 'folders', 'files', 'storageUsed', 'storageQuota'));
    }

    /**
     * Upload file
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'folder_id' => 'nullable|exists:drive_folders,id',
        ]);

        $file = $request->file('file');
        $folderId = $validated['folder_id'];

        // Verify folder ownership if provided
        if ($folderId) {
            $folder = DriveFolder::findOrFail($folderId);
            abort_unless($folder->user_id === auth()->id(), 403);
        }

        // Store file
        $path = $file->store('drive/' . auth()->id() . '/' . date('Y/m/d'));
        $size = $file->getSize();

        // Create file record
        $driveFile = DriveFile::create([
            'user_id' => auth()->id(),
            'folder_id' => $folderId,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $size,
            'storage_path' => $path,
            'hash' => hash_file('sha256', $file->path()),
        ]);

        // Increment the user's aggregate storage counter (single source of truth)
        auth()->user()->increment('storage_used', $size);

        // Publish event
        $this->eventService->publish(
            'drive',
            'file_uploaded',
            [
                'file_id' => $driveFile->id,
                'name' => $driveFile->name,
                'size' => $size,
            ],
            auth()->id()
        );

        return redirect()->back()->with('success', 'File uploaded successfully!');
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:drive_folders,id',
        ]);

        // Verify parent folder ownership
        if ($validated['parent_id']) {
            $parent = DriveFolder::findOrFail($validated['parent_id']);
            abort_unless($parent->user_id === auth()->id(), 403);
        }

        $folder = DriveFolder::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'],
        ]);

        return redirect()->back()->with('success', 'Folder created successfully!');
    }

    /**
     * Download file
     */
    public function download($fileId)
    {
        $file = DriveFile::findOrFail($fileId);
        
        // Verify ownership or share permission
        $isOwner = $file->user_id === auth()->id();
        $isShared = $file->shares()->where('shared_with_user_id', auth()->id())->exists();
        
        abort_unless($isOwner || $isShared, 403);

        return Storage::download($file->storage_path, $file->name);
    }

    /**
     * Share file
     */
    public function share(Request $request, $fileId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'permission' => 'required|in:view,edit,comment',
        ]);

        $file = DriveFile::findOrFail($fileId);
        abort_unless($file->user_id === auth()->id(), 403);

        $file->shares()->create([
            'shared_with_email' => $validated['email'],
            'permission' => $validated['permission'],
        ]);

        // TODO: Send email notification to shared user

        return redirect()->back()->with('success', 'File shared successfully!');
    }

    /**
     * Delete file (move to trash)
     */
    public function destroy($fileId)
    {
        $file = DriveFile::findOrFail($fileId);
        abort_unless($file->user_id === auth()->id(), 403);

        // Move to trash
        \App\Models\DriveTrash::create([
            'user_id' => auth()->id(),
            'item_type' => 'file',
            'item_id' => $file->id,
            'deleted_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $file->delete();

        return redirect()->back()->with('success', 'File moved to trash');
    }
}
