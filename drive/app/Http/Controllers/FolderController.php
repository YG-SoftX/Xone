<?php

namespace App\Http\Controllers;

use App\Models\DriveFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:drive_folders,id',
            'color'     => 'nullable|string|max:7',
        ]);

        $folder = DriveFolder::create([
            'user_id'   => Auth::id(),
            'name'      => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'color'     => $data['color'] ?? '#4a86e8',
        ]);

        return response()->json(['folder' => $folder, 'message' => 'Folder created.']);
    }

    public function update(Request $request, DriveFolder $folder)
    {
        abort_unless(Auth::id() === $folder->user_id, 403);
        $folder->update($request->only(['name', 'color', 'description']));
        return response()->json(['folder' => $folder, 'message' => 'Folder updated.']);
    }

    public function destroy(DriveFolder $folder)
    {
        abort_unless(Auth::id() === $folder->user_id, 403);
        $folder->update(['is_trashed' => true]);
        return response()->json(['message' => 'Folder moved to trash.']);
    }

    public function star(DriveFolder $folder)
    {
        abort_unless(Auth::id() === $folder->user_id, 403);
        $folder->update(['is_starred' => !$folder->is_starred]);
        return response()->json(['starred' => $folder->is_starred]);
    }
}
