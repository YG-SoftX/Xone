<?php

namespace App\Http\Controllers;

use App\Models\SheetShare;
use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ShareController extends Controller
{
    /**
     * List all shares for a spreadsheet.
     */
    public function index(Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->canAccess(auth()->id()), 403);

        try {
            $shares = $spreadsheet->shares()
                ->with(['user', 'createdBy'])
                ->latest()
                ->get();

            return Inertia::render('Spreadsheets/Shares/Index', [
                'spreadsheet' => $spreadsheet,
                'shares' => $shares,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list spreadsheet shares', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load shares. Please try again.');
        }
    }

    /**
     * Share a spreadsheet with a user/email + permission.
     */
    public function store(Request $request, Spreadsheet $spreadsheet)
    {
        abort_unless($spreadsheet->user_id === auth()->id(), 403);

        try {
            $validated = $request->validate([
                'email' => 'nullable|email',
                'user_id' => 'nullable|integer|exists:users,id',
                'permission' => 'required|string|in:view,edit',
            ]);

            if (empty($validated['email']) && empty($validated['user_id'])) {
                return redirect()->back()->with('error', 'You must provide either an email or a user ID.');
            }

            // If user_id provided, get the email
            $email = $validated['email'];
            if ($validated['user_id'] && !$email) {
                $user = User::find($validated['user_id']);
                if ($user) {
                    $email = $user->email;
                }
            }

            // Prevent owner from sharing to themselves
            if ($email === $spreadsheet->user->email) {
                return redirect()->back()->with('error', 'You already own this spreadsheet.');
            }

            // Check if share already exists
            $existingShare = SheetShare::where('spreadsheet_id', $spreadsheet->id)
                ->where('email', $email)
                ->first();

            if ($existingShare) {
                return redirect()->back()->with('error', 'This user already has access to this spreadsheet.');
            }

            $share = SheetShare::create([
                'spreadsheet_id' => $spreadsheet->id,
                'user_id' => $validated['user_id'] ?? null,
                'email' => $email,
                'permission' => $validated['permission'],
                'created_by' => $request->user()->id,
            ]);

            return redirect()->back()->with('success', 'Spreadsheet shared successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to share spreadsheet', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $spreadsheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to share spreadsheet. Please try again.');
        }
    }

    /**
     * Update the permission of an existing share.
     */
    public function update(Request $request, SheetShare $share)
    {
        abort_unless($share->spreadsheet->user_id === auth()->id(), 403);

        try {
            $validated = $request->validate([
                'permission' => 'required|string|in:view,edit',
            ]);

            $share->update($validated);

            return redirect()->back()->with('success', 'Share permission updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update share permission', [
                'error' => $e->getMessage(),
                'share_id' => $share->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update permission. Please try again.');
        }
    }

    /**
     * Revoke access for a share.
     */
    public function revoke(SheetShare $share)
    {
        abort_unless($share->spreadsheet->user_id === auth()->id(), 403);

        try {
            $share->delete();

            return redirect()->back()->with('success', 'Access revoked successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to revoke share', [
                'error' => $e->getMessage(),
                'share_id' => $share->id,
            ]);

            return redirect()->back()->with('error', 'Failed to revoke access. Please try again.');
        }
    }
}
