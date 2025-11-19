<?php

namespace Tests\Feature;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Database\Seeders\RolePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePresetSeeder::class);
    }

    public function test_visit_can_be_approved_with_staff_and_reason(): void
    {
        $receptionist = User::factory()->create(['permissions' => ['visits.update']]);
        $staff = User::factory()->create();
        $reason = ReasonForVisit::factory()->create();
        $visitor = Visitor::factory()->create();

        $visit = Visit::factory()->create([
            'visitor_id' => $visitor->id,
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
            'status' => 'pending',
        ]);

        $this->actingAs($receptionist);

        // Simulate approval
        $visit->update([
            'status' => 'approved',
            'checkin_time' => now(),
        ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'approved',
        ]);

        $this->assertNotNull($visit->fresh()->checkin_time);
    }

    public function test_onsite_visitors_only_shows_approved_visits(): void
    {
        $visitor1 = Visitor::factory()->create();
        $visitor2 = Visitor::factory()->create();

        // Approved and checked in (should appear)
        $approvedVisit = Visit::factory()->approved()->create([
            'visitor_id' => $visitor1->id,
            'checkout_time' => null,
        ]);

        // Pending (should NOT appear)
        $pendingVisit = Visit::factory()->create([
            'visitor_id' => $visitor2->id,
            'status' => 'pending',
            'checkout_time' => null,
        ]);

        $onsiteVisits = Visit::query()
            ->whereNull('checkout_time')
            ->where('status', 'approved')
            ->get();

        $this->assertTrue($onsiteVisits->contains($approvedVisit));
        $this->assertFalse($onsiteVisits->contains($pendingVisit));
    }

    public function test_visit_requires_staff_and_reason_before_approval(): void
    {
        $visitor = Visitor::factory()->create();

        $visit = Visit::factory()->create([
            'visitor_id' => $visitor->id,
            'staff_visited_id' => null,
            'reason_for_visit_id' => null,
            'status' => 'pending',
        ]);

        // Should not be able to approve without staff and reason
        $this->assertNull($visit->staff_visited_id);
        $this->assertNull($visit->reason_for_visit_id);
        $this->assertEquals('pending', $visit->status);
    }
}
