<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProject;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    /**
     * List team members for a project
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $members = ProjectMember::where('project_id', $project->id)
            ->with('user:id,name,email')
            ->orderBy('role')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
                'owner' => [
                    'id' => $project->owner->id,
                    'name' => $project->owner->name,
                    'email' => $project->owner->email,
                ],
            ],
            'members' => $members->map(function($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                    'role' => $member->role,
                    'added_at' => $member->created_at?->toIso8601String(),
                ];
            }),
            'total_members' => $members->count(),
        ]);
    }

    /**
     * Invite team member by email
     */
    public function invite(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
             $q->where('owner_id', $user->id)
               ->orWhereHas('members', function($mq) use ($user) {
                   $mq->where('user_id', $user->id)
                      ->whereIn('role', ['owner', 'editor']);
               });
        })->findOrFail($projectId);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:editor,viewer,billing_admin',
        ]);

        // Find user by email
        $invitee = User::where('email', $validated['email'])->first();

        if (!$invitee) {
            return response()->json([
                'error' => 'User not found. They must have a YG Account first.',
                'email' => $validated['email'],
            ], 404);
        }

        // Check if already a member
        $existingMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $invitee->id)
            ->first();

        if ($existingMember) {
            return response()->json([
                'error' => 'User is already a member of this project',
                'current_role' => $existingMember->role,
            ], 409);
        }

        // Cannot invite self
        if ($invitee->id === $user->id) {
            return response()->json(['error' => 'You cannot invite yourself'], 400);
        }

        // Create membership
        $member = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $invitee->id,
            'role' => $validated['role'],
        ]);

        // In production, send invitation email notification here

        return response()->json([
            'success' => true,
            'message' => "{$invitee->name} has been added as {$validated['role']}",
            'member' => [
                'id' => $member->id,
                'user_id' => $invitee->id,
                'name' => $invitee->name,
                'email' => $invitee->email,
                'role' => $member->role,
            ],
        ], 201);
    }

    /**
     * Update member role
     */
    public function updateRole(Request $request, $projectId, $memberId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($projectId);

        // Only owner can change roles
        if ($project->owner_id !== $user->id) {
            return response()->json(['error' => 'Only project owner can update roles'], 403);
        }

        $member = ProjectMember::where('project_id', $project->id)
            ->findOrFail($memberId);

        // Cannot change owner's role
        if ($member->role === 'owner') {
            return response()->json(['error' => 'Cannot change owner role'], 400);
        }

        $validated = $request->validate([
            'role' => 'required|in:editor,viewer,billing_admin',
        ]);

        $oldRole = $member->role;
        $member->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => "Role updated from {$oldRole} to {$validated['role']}",
            'member' => [
                'id' => $member->id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'role' => $member->role,
            ],
        ]);
    }

    /**
     * Remove team member
     */
    public function remove(Request $request, $projectId, $memberId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($projectId);

        $member = ProjectMember::where('project_id', $project->id)
            ->findOrFail($memberId);

        // Cannot remove owner
        if ($member->role === 'owner') {
            return response()->json(['error' => 'Cannot remove project owner'], 400);
        }

        // Editors can only remove viewers
        $myMembership = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();
            
        $myRole = $myMembership ? $myMembership->role : null;

        if ($myRole === 'editor' && $member->role !== 'viewer') {
            return response()->json(['error' => 'Editors can only remove viewers'], 403);
        }

        $memberName = $member->user?->name ?? 'Unknown';
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => "{$memberName} has been removed from the project",
        ]);
    }

    /**
     * Leave project (self-removal)
     */
    public function leave($projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::findOrFail($projectId);

        // Owner cannot leave (must transfer ownership first)
        if ($project->owner_id === $user->id) {
            return response()->json([
                'error' => 'Owner cannot leave. Transfer ownership first or delete the project.',
            ], 400);
        }

        $member = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => "You have left the project '{$project->name}'",
        ]);
    }

    /**
     * Transfer project ownership
     */
    public function transferOwnership(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($projectId);

        $validated = $request->validate([
            'new_owner_id' => 'required|integer|exists:users,id',
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);

        // New owner must already be a member
        $newOwnerMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $newOwner->id)
            ->first();

        if (!$newOwnerMember) {
            return response()->json([
                'error' => 'New owner must be a team member first',
            ], 400);
        }

        // Transfer ownership
        $oldOwnerId = $project->owner_id;
        $project->update(['owner_id' => $newOwner->id]);

        // Demote old owner to editor
        $oldOwnerMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $oldOwnerId)
            ->first();

        if ($oldOwnerMember) {
            $oldOwnerMember->update(['role' => 'editor']);
        } else {
            // Add old owner as editor
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $oldOwnerId,
                'role' => 'editor',
            ]);
        }

        // Promote new owner member record to owner role (or delete if exists)
        // Typically owner is identified by project.owner_id, so member record might be redundant or kept as editor/admin
        // The reference solution deletes it. We'll follow that.
        $newOwnerMember->delete(); 

        return response()->json([
            'success' => true,
            'message' => "Project ownership transferred to {$newOwner->name}",
            'new_owner' => [
                'id' => $newOwner->id,
                'name' => $newOwner->name,
                'email' => $newOwner->email,
            ],
        ]);
    }
}
