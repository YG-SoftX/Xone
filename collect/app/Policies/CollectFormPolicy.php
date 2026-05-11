<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CollectForm;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectFormPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CollectForm');
    }

    public function view(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('View:CollectForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CollectForm');
    }

    public function update(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('Update:CollectForm');
    }

    public function delete(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('Delete:CollectForm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CollectForm');
    }

    public function restore(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('Restore:CollectForm');
    }

    public function forceDelete(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('ForceDelete:CollectForm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CollectForm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CollectForm');
    }

    public function replicate(AuthUser $authUser, CollectForm $collectForm): bool
    {
        return $authUser->can('Replicate:CollectForm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CollectForm');
    }

}