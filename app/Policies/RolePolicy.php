<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): Response
    {
        return Response::deny();
    }

    public function view(User $user, Role $role): Response
    {
        return Response::deny();
    }

    public function create(User $user): Response
    {
        return Response::deny();
    }

    public function update(User $user, Role $role): Response
    {
        return Response::deny();
    }

    public function delete(User $user, Role $role): Response
    {
        return Response::deny();
    }

    public function deleteAny(User $user): Response
    {
        return Response::deny();
    }
}
