<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mail;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount(['mails'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === 1) {
            return back()->with('error', 'Cannot delete the primary admin account.');
        }

        // Delete all user's emails first
        Mail::where('user_id', $user->id)->delete();

        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:active,suspended']);
        $user = User::findOrFail($id);
        $user->update(['status' => $request->status]);
        return back()->with('success', 'User status updated.');
    }
}
