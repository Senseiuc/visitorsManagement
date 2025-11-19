<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->hasPermission('locations.view');
    }

    public function view(User $user, Location $location): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $location->id, array_map('intval', $ids), true)
                && $user->hasPermission('locations.view');
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->hasPermission('locations.create');
        }

        return false;
    }

    public function update(User $user, Location $location): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $location->id, array_map('intval', $ids), true)
                && $user->hasPermission('locations.update');
        }

        return false;
    }

    public function delete(User $user, Location $location): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) $location->id, array_map('intval', $ids), true)
                && $user->hasPermission('locations.delete');
        }

        return false;
    }
}
