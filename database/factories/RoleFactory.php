<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Manager',
            'Supervisor',
            'Team Lead',
            'Coordinator',
        ]);

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'permissions' => [],
        ];
    }

    /**
     * Indicate that the role is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'superadmin',
            'permissions' => ['*'],
        ]);
    }

    /**
     * Indicate that the role is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => [
                'users.view', 'users.create', 'users.update', 'users.delete',
                'visitors.view', 'visitors.create', 'visitors.update', 'visitors.delete',
                'visits.view', 'visits.create', 'visits.update', 'visits.delete',
                'blacklist.view', 'blacklist.update',
            ],
        ]);
    }

    /**
     * Indicate that the role is a receptionist.
     */
    public function receptionist(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Receptionist',
            'slug' => 'receptionist',
            'permissions' => [
                'visitors.view', 'visitors.create', 'visitors.update',
                'visits.view', 'visits.update',
            ],
        ]);
    }

    /**
     * Indicate that the role is staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Staff',
            'slug' => 'staff',
            'permissions' => ['visits.view'],
        ]);
    }
}
