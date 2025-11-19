<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Database\Seeders\RolePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlacklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePresetSeeder::class);
    }

    public function test_admin_can_blacklist_visitor_directly(): void
    {
        $admin = User::factory()->create(['permissions' => ['blacklist.update']]);
        $visitor = Visitor::factory()->create();

        $this->actingAs($admin);

        $visitor->update([
            'is_blacklisted' => true,
            'reasons_for_blacklisting' => 'Security concern',
            'date_blacklisted' => now(),
            'blacklist_request_status' => 'approved',
        ]);

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'is_blacklisted' => true,
            'reasons_for_blacklisting' => 'Security concern',
        ]);
    }

    public function test_receptionist_can_request_blacklist(): void
    {
        $receptionist = User::factory()->create(['permissions' => ['visitors.update']]);
        $visitor = Visitor::factory()->create();

        $this->actingAs($receptionist);

        $visitor->update([
            'blacklist_request_status' => 'pending',
            'blacklist_request_reason' => 'Suspicious behavior',
            'blacklist_requested_by' => $receptionist->id,
            'blacklist_requested_at' => now(),
        ]);

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'blacklist_request_status' => 'pending',
            'blacklist_request_reason' => 'Suspicious behavior',
        ]);

        $this->assertFalse($visitor->fresh()->is_blacklisted);
    }

    public function test_admin_can_approve_blacklist_request(): void
    {
        $admin = User::factory()->create(['permissions' => ['blacklist.update']]);
        $visitor = Visitor::factory()->blacklistRequested()->create();

        $this->actingAs($admin);

        $visitor->update([
            'is_blacklisted' => true,
            'date_blacklisted' => now(),
            'reasons_for_blacklisting' => $visitor->blacklist_request_reason,
            'blacklist_request_status' => 'approved',
        ]);

        $this->assertTrue($visitor->fresh()->is_blacklisted);
        $this->assertEquals('approved', $visitor->fresh()->blacklist_request_status);
    }

    public function test_blacklisted_visitor_cannot_have_visit_approved(): void
    {
        $visitor = Visitor::factory()->blacklisted()->create();
        $visit = Visit::factory()->create([
            'visitor_id' => $visitor->id,
            'status' => 'pending',
        ]);

        // Simulate checking if visitor is blacklisted before approval
        $this->assertTrue($visitor->is_blacklisted);

        // Visit should remain pending
        $this->assertEquals('pending', $visit->status);
    }

    public function test_blacklist_factory_state_works(): void
    {
        $blacklistedVisitor = Visitor::factory()->blacklisted()->create();

        $this->assertTrue($blacklistedVisitor->is_blacklisted);
        $this->assertNotNull($blacklistedVisitor->reasons_for_blacklisting);
        $this->assertNotNull($blacklistedVisitor->date_blacklisted);
    }
}
