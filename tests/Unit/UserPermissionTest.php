<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePresetSeeder::class);
    }

    public function test_has_permission_returns_true_for_granted_permission(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view', 'visits.update'],
        ]);

        $this->assertTrue($user->hasPermission('visitors.view'));
        $this->assertTrue($user->hasPermission('visits.update'));
    }

    public function test_has_permission_returns_false_for_denied_permission(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view'],
        ]);

        $this->assertFalse($user->hasPermission('users.delete'));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $role = Role::where('slug', 'superadmin')->first();
        
        if (!$role) {
            $role = Role::factory()->superAdmin()->create();
        }
        
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('any.random.permission'));
    }

    public function test_effective_permissions_includes_role_permissions(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'permissions' => ['custom.permission'],
        ]);

        $effectivePermissions = $user->effectivePermissions();

        $this->assertContains('custom.permission', $effectivePermissions);
        $this->assertContains('visitors.view', $effectivePermissions);
        $this->assertContains('blacklist.update', $effectivePermissions);
    }

    public function test_has_any_permission_returns_true_if_one_matches(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view'],
        ]);

        $this->assertTrue($user->hasAnyPermission(['visitors.view', 'users.delete']));
    }

    public function test_has_any_permission_returns_false_if_none_match(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view'],
        ]);

        $this->assertFalse($user->hasAnyPermission(['users.delete', 'users.create']));
    }

    public function test_role_helper_methods_work_correctly(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $staffRole = Role::where('slug', 'staff')->first();

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $staff = User::factory()->create(['role_id' => $staffRole->id]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStaff());

        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isAdmin());
    }
}
