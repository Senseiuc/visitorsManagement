<?php

namespace App\Support;

class Permissions
{
    /**
     * Return all available permission keys mapped to human labels.
     * Provides granular CRUD per resource.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $resources = [
            'locations' => 'Locations',
            'floors' => 'Floors',
            'departments' => 'Departments',
            'users' => 'Users/Staff',
            'visitors' => 'Visitors',
            'visits' => 'Visits',
            'blacklist' => 'Blacklist',
            'roles' => 'Roles',
            'reasons' => 'Reasons for Visit',
        ];

        $perms = [];
        foreach ($resources as $slug => $label) {
            $perms["{$slug}.view"] = "View {$label}";
            $perms["{$slug}.create"] = "Create {$label}";
            $perms["{$slug}.update"] = "Edit {$label}";
            $perms["{$slug}.delete"] = "Delete {$label}";
        }

        return $perms;
    }
}
