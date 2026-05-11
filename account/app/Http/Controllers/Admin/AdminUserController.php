<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    private function adminUser(): ?User
    {
        return User::find(session('admin_user_id'));
    }

    private function isSuperAdmin(): bool
    {
        return $this->adminUser()?->role === 'super_admin';
    }

    private function auditLog(Request $request, string $description): void
    {
        ActivityLog::record(
            session('admin_user_id'),
            $description,
            'Admin',
            '🔧',
            $request->ip()
        );
    }

    // ── Controllers ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = User::with(['wallet', 'subscriptions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::with([
            'wallet',
            'transactions',
            'subscriptions',
            'activityLogs',
            'savedCredentials',
            'kyc',
            'devices',
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended,deleted',
        ]);

        $user = User::findOrFail($id);
        $previous = $user->status;

        // Use the protected setter so status bypasses $fillable restriction
        $user->setStatus($request->status);

        $this->auditLog($request, "Changed user #{$user->id} ({$user->email}) status: {$previous} → {$request->status}");

        return redirect()->back()->with('success', 'User status updated.');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:user,admin,moderator,super_admin',
        ]);

        $user = User::findOrFail($id);

        // Only super_admin may elevate another user to admin or super_admin
        if (in_array($request->role, ['admin', 'super_admin']) && !$this->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Only a super_admin can assign elevated roles.');
        }

        $previous = $user->role;

        // Use the protected setter so role bypasses $fillable restriction
        $user->assignRole($request->role);

        $this->auditLog($request, "Changed user #{$user->id} ({$user->email}) role: {$previous} → {$request->role}");

        return redirect()->back()->with('success', 'User role updated.');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === session('admin_user_id')) {
            return redirect()->back()->with('error', 'Cannot delete your own account.');
        }

        $this->auditLog($request, "Deleted user #{$user->id} ({$user->email})");

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function impersonate(Request $request, $id)
    {
        if (!$this->isSuperAdmin()) {
            Log::warning('Unauthorised impersonation attempt', [
                'admin_user_id'  => session('admin_user_id'),
                'target_user_id' => $id,
                'ip'             => $request->ip(),
            ]);

            return redirect()->back()->with('error', 'Only a super_admin can impersonate users.');
        }

        $user = User::findOrFail($id);

        if (in_array($user->role, ['admin', 'super_admin'])) {
            return redirect()->back()->with('error', 'Cannot impersonate another admin account.');
        }

        $now = time();

        session([
            'impersonating_user_id'  => $user->id,
            'impersonation_admin_id' => session('admin_user_id'),
            'impersonation_started'  => $now,
            'impersonation_expires'  => $now + 3600,
        ]);

        $this->auditLog($request, "Started impersonation of user #{$user->id} ({$user->email}), expires in 1 hour");

        Log::warning('Admin impersonation started', [
            'admin_user_id'  => session('admin_user_id'),
            'target_user_id' => $user->id,
            'target_email'   => $user->email,
            'ip'             => $request->ip(),
            'expires_at'     => date('Y-m-d H:i:s', $now + 3600),
        ]);

        return redirect('/dashboard')->with('info', 'You are now impersonating ' . $user->name . '. Session expires in 1 hour.');
    }

    public function kycReview()
    {
        $kycSubmissions = \App\Models\KYC::with('user')
            ->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.kyc.index', compact('kycSubmissions'));
    }

    public function kycApprove(Request $request, $id)
    {
        $request->validate(['notes' => 'nullable|string']);

        $kyc = \App\Models\KYC::findOrFail($id);
        $kyc->update([
            'status'      => 'approved',
            'admin_notes' => $request->notes,
            'reviewed_at' => now(),
            'reviewed_by' => session('admin_user_id'),
        ]);

        // Use the protected setter — kyc_status is not in $fillable
        $kyc->user->setKycStatus('approved');

        $this->auditLog($request, "Approved KYC #{$kyc->id} for user #{$kyc->user_id}");

        return redirect()->back()->with('success', 'KYC approved.');
    }

    public function kycReject(Request $request, $id)
    {
        $request->validate(['notes' => 'required|string']);

        $kyc = \App\Models\KYC::findOrFail($id);
        $kyc->update([
            'status'      => 'rejected',
            'admin_notes' => $request->notes,
            'reviewed_at' => now(),
            'reviewed_by' => session('admin_user_id'),
        ]);

        // Use the protected setter — kyc_status is not in $fillable
        $kyc->user->setKycStatus('rejected');

        $this->auditLog($request, "Rejected KYC #{$kyc->id} for user #{$kyc->user_id}");

        return redirect()->back()->with('success', 'KYC rejected.');
    }
}
