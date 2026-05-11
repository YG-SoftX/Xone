<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CollectProject;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectProjectPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CollectProject');
    }

    public function view(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('View:CollectProject');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CollectProject');
    }

    public function update(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('Update:CollectProject');
    }

    public function delete(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('Delete:CollectProject');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CollectProject');
    }

    public function restore(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('Restore:CollectProject');
    }

    public function forceDelete(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('ForceDelete:CollectProject');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CollectProject');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CollectProject');
    }

    public function replicate(AuthUser $authUser, CollectProject $collectProject): bool
    {
        return $authUser->can('Replicate:CollectProject');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CollectProject');
    }

}