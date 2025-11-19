<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): Response
    {
        if ($user->isAdmin() && $user->hasAnyPermission(['users.view'])) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, User $model): Response
    {
        if ($user->isAdmin() && $user->hasAnyPermission(['users.view'])) {
            // Admins can view receptionists they created or who share any of their accessible locations
            $adminLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($adminLocs, $targetLocs)) > 0;

            $can = $model->isReceptionist() && ($model->created_by_user_id === $user->id || $sharesLocation);

            return $can ? Response::allow() : Response::deny();
        }

        // Receptionists cannot view other users in admin panel
        return Response::deny();
    }

    public function create(User $user): Response
    {
        if ($user->isAdmin() && $user->hasAnyPermission(['users.create'])) {
            // Admins can create only receptionists and only in their location
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, User $model): Response
    {
        if ($user->isAdmin() && $user->hasAnyPermission(['users.update'])) {
            // Admins can update only receptionists they created or who share any of their accessible locations
            $adminLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($adminLocs, $targetLocs)) > 0;

            $can = $model->isReceptionist() && ($model->created_by_user_id === $user->id || $sharesLocation);

            // Admins cannot change superadmins/admins, and cannot update other admins
            return $can ? Response::allow() : Response::deny();
        }

        return Response::deny();
    }

    public function delete(User $user, User $model): Response
    {
        if ($model->id === $user->id) {
            return Response::deny(); // cannot delete self
        }

        if ($user->isAdmin() && $user->hasAnyPermission(['users.delete'])) {
            $adminLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($adminLocs, $targetLocs)) > 0;

            $can = $model->isReceptionist() && ($model->created_by_user_id === $user->id || $sharesLocation);

            return $can ? Response::allow() : Response::deny();
        }

        return Response::deny();
    }

    public function deleteAny(User $user): Response
    {
        if ($user->isAdmin() && $user->hasAnyPermission(['users.delete'])) {
            return Response::allow();
        }

        return Response::deny();
    }
}
