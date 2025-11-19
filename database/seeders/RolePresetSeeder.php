<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Permissions as Perms;
use Illuminate\Database\Seeder;

class RolePresetSeeder extends Seeder
{
    public function run(): void
    {
        // Build canonical permission keys
        $all = array_keys(Perms::all());

        // Helper to include all CRUD for a resource
        $crud = function (string $resource) use ($all): array {
            return array_values(array_filter($all, fn ($k) => str_starts_with($k, $resource . '.')));
        };

        // ADMIN: full CRUD on most resources + ability to view/update blacklist
        $adminPermissions = array_values(array_unique(array_merge(
            $crud('locations'),
            $crud('floors'),
            $crud('departments'),
            $crud('users'),
            $crud('visitors'),
            $crud('visits'),
            // Blacklist: only view and update by default
            ['blacklist.view', 'blacklist.update']
        )));

        // RECEPTIONIST: create visits, view visitors (optionally create visitors)
        $receptionistPermissions = [
            'visits.view', 'visits.create',
            // Allow limited inline updates if needed; keep delete off by default
            // Comment out the next line if you want receptionists strictly create-only
            'visits.update',

            // Visitors visibility (and optional create to register new visitors)
            'visitors.view', 'visitors.create',
        ];

        // STAFF: minimal read-only access
        $staffPermissions = [
            'visits.view',
            'visitors.view',
        ];

        // Upsert roles idempotently
        $this->upsertRole('admin', 'Admin', $adminPermissions);
        $this->upsertRole('receptionist', 'Receptionist', $receptionistPermissions);
        $this->upsertRole('staff', 'Staff', $staffPermissions);
    }

    protected function upsertRole(string $slug, string $name, array $permissions): void
    {
        Role::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'permissions' => array_values(array_unique($permissions)),
            ],
        );
    }
}
