<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CollectSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectSubmissionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CollectSubmission');
    }

    public function view(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('View:CollectSubmission');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CollectSubmission');
    }

    public function update(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('Update:CollectSubmission');
    }

    public function delete(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('Delete:CollectSubmission');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CollectSubmission');
    }

    public function restore(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('Restore:CollectSubmission');
    }

    public function forceDelete(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('ForceDelete:CollectSubmission');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CollectSubmission');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CollectSubmission');
    }

    public function replicate(AuthUser $authUser, CollectSubmission $collectSubmission): bool
    {
        return $authUser->can('Replicate:CollectSubmission');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CollectSubmission');
    }

}