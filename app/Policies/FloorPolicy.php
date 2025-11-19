<?php

namespace App\Policies;

use App\Models\Floor;
use App\Models\User;

class FloorPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->hasPermission('floors.view');
    }

    public function view(User $user, Floor $floor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $floor->location_id, array_map('intval', $ids), true)
                && $user->hasPermission('floors.view');
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->hasPermission('floors.create') && !empty($user->accessibleLocationIds());
        }

        return false;
    }

    public function update(User $user, Floor $floor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $floor->location_id, array_map('intval', $ids), true)
                && $user->hasPermission('floors.update');
        }

        return false;
    }

    public function delete(User $user, Floor $floor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $floor->location_id, array_map('intval', $ids), true)
                && $user->hasPermission('floors.delete');
        }

        return false;
    }
}
