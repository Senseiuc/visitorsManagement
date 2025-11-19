<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->hasPermission('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) optional($department->floor)->location_id, array_map('intval', $ids), true)
                && $user->hasPermission('departments.view');
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            return $user->hasPermission('departments.create') && !empty($user->accessibleLocationIds());
        }

        return false;
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds() ?? [];
            return in_array((int) optional($department->floor)->location_id, array_map('intval', $ids), true)
                && $user->hasPermission('departments.update');
        }

        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            return ((int) optional($department->floor)->location_id === (int) $user->assigned_location_id)
                && $user->hasPermission('departments.delete');
        }

        return false;
    }
}
