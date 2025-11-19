<?php

namespace Tests\Unit;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_belongs_to_visitor(): void
    {
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create(['visitor_id' => $visitor->id]);

        $this->assertInstanceOf(Visitor::class, $visit->visitor);
        $this->assertEquals($visitor->id, $visit->visitor->id);
    }

    public function test_visit_belongs_to_staff(): void
    {
        $staff = User::factory()->create();
        $visit = Visit::factory()->create(['staff_visited_id' => $staff->id]);

        $this->assertInstanceOf(User::class, $visit->staff);
        $this->assertEquals($staff->id, $visit->staff->id);
    }

    public function test_visit_belongs_to_reason(): void
    {
        $reason = ReasonForVisit::factory()->create();
        $visit = Visit::factory()->create(['reason_for_visit_id' => $reason->id]);

        $this->assertInstanceOf(ReasonForVisit::class, $visit->reason);
        $this->assertEquals($reason->id, $visit->reason->id);
    }

    public function test_approved_visit_has_correct_status(): void
    {
        $visit = Visit::factory()->approved()->create();

        $this->assertEquals('approved', $visit->status);
        $this->assertNotNull($visit->checkin_time);
        $this->assertNull($visit->checkout_time);
    }

    public function test_checked_out_visit_has_both_times(): void
    {
        $visit = Visit::factory()->checkedOut()->create();

        $this->assertEquals('approved', $visit->status);
        $this->assertNotNull($visit->checkin_time);
        $this->assertNotNull($visit->checkout_time);
        $this->assertGreaterThan($visit->checkin_time, $visit->checkout_time);
    }

    public function test_onsite_visit_has_no_checkout_time(): void
    {
        $visit = Visit::factory()->onSite()->create();

        $this->assertEquals('approved', $visit->status);
        $this->assertNotNull($visit->checkin_time);
        $this->assertNull($visit->checkout_time);
    }

    public function test_pending_visit_has_no_checkin_time(): void
    {
        $visit = Visit::factory()->create(['status' => 'pending']);

        $this->assertEquals('pending', $visit->status);
        $this->assertNull($visit->checkin_time);
    }
}
