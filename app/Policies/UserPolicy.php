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
        if ($user->hasPermission('users.view')) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, User $model): Response
    {
        if ($user->hasPermission('users.view')) {
            // Can view if shares location
            $userLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($userLocs, $targetLocs)) > 0;

            // Cannot view superadmins unless you are one (handled by before)
            if ($model->isSuperAdmin()) {
                return Response::deny();
            }

            return $sharesLocation ? Response::allow() : Response::deny();
        }

        return Response::deny();
    }

    public function create(User $user): Response
    {
        if ($user->hasPermission('users.create')) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, User $model): Response
    {
        if ($user->hasPermission('users.update')) {
            // Can update if shares location
            $userLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($userLocs, $targetLocs)) > 0;

            // Cannot update superadmins
            if ($model->isSuperAdmin()) {
                return Response::deny();
            }

            return $sharesLocation ? Response::allow() : Response::deny();
        }

        return Response::deny();
    }

    public function delete(User $user, User $model): Response
    {
        if ($model->id === $user->id) {
            return Response::deny(); // cannot delete self
        }

        if ($user->hasPermission('users.delete')) {
            $userLocs = $user->locationIds();
            $targetLocs = $model->locationIds();
            $sharesLocation = count(array_intersect($userLocs, $targetLocs)) > 0;

            if ($model->isSuperAdmin()) {
                return Response::deny();
            }

            return $sharesLocation ? Response::allow() : Response::deny();
        }

        return Response::deny();
    }

    public function deleteAny(User $user): Response
    {
        if ($user->hasPermission('users.delete')) {
            return Response::allow();
        }

        return Response::deny();
    }
}
