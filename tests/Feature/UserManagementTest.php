<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePresetSeeder::class);
    }

    public function test_user_can_be_created_with_role(): void
    {
        $role = Role::where('slug', 'staff')->first();

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => $role->id,
        ]);

        $this->assertTrue($user->isStaff());
    }

    public function test_user_has_permission_check_works(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view', 'visits.view'],
        ]);

        $this->assertTrue($user->hasPermission('visitors.view'));
        $this->assertTrue($user->hasPermission('visits.view'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $role = Role::where('slug', 'superadmin')->first();
        
        if (!$role) {
            $role = Role::factory()->superAdmin()->create();
        }
        
        $superAdmin = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->hasPermission('any.permission'));
        $this->assertTrue($superAdmin->hasPermission('users.delete'));
    }

    public function test_role_based_permissions_work(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $effectivePermissions = $user->effectivePermissions();

        $this->assertContains('visitors.view', $effectivePermissions);
        $this->assertContains('blacklist.update', $effectivePermissions);
    }

    public function test_user_can_have_multiple_permissions(): void
    {
        $user = User::factory()->create([
            'permissions' => ['visitors.view', 'visits.view', 'users.view'],
        ]);

        $this->assertCount(3, $user->permissions);
        $this->assertTrue($user->hasPermission('visitors.view'));
        $this->assertTrue($user->hasPermission('visits.view'));
        $this->assertTrue($user->hasPermission('users.view'));
    }
}
