<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions as Perms;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ensure a Super Admin role exists with ALL permissions
        $allPermissions = array_keys(Perms::all());

        $role = Role::query()->updateOrCreate(
            ['slug' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'permissions' => $allPermissions,
            ]
        );

        // 2) Create or update a Super Admin user and assign the role
        $name = 'Super Admin';
        $email = 'admin@vms.com';
        $password = 'vms@2025';

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password, // hashed by model cast
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $role->id,
            ]
        );

        // Ensure the pivot contains the superadmin role as well
        $user->roles()->syncWithoutDetaching([$role->id]);

        // Keep legacy single role_id in sync for backward compatibility
        if ($user->role_id !== $role->id) {
            $user->forceFill(['role_id' => $role->id])->save();
        }
    }
}
