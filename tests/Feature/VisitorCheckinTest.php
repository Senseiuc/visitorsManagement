<?php

namespace Tests\Feature;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\ReasonForVisitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorCheckinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReasonForVisitSeeder::class);
    }

    public function test_visitor_lookup_page_loads(): void
    {
        $response = $this->get(route('visitor.lookup'));

        $response->assertStatus(200);
        $response->assertViewIs('visitor.lookup');
    }

    public function test_existing_visitor_can_be_found_by_email(): void
    {
        $visitor = Visitor::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post(route('visitor.postLookup'), [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('visitor.existing', $visitor));
    }

    public function test_new_visitor_redirects_to_registration_form(): void
    {
        $response = $this->post(route('visitor.postLookup'), [
            'email' => 'newvisitor@example.com',
        ]);

        $response->assertRedirect(route('visitor.new'));
    }

    public function test_new_visitor_can_register_and_checkin(): void
    {
        $staff = User::factory()->create();
        $reason = ReasonForVisit::first();

        $response = $this->post(route('visitor.checkin'), [
            'mode' => 'new',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile' => '+234 801 234 5678',
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
        ]);

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->assertDatabaseHas('visits', [
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('visitor.success'));
    }

    public function test_existing_visitor_can_checkin(): void
    {
        $visitor = Visitor::factory()->create();
        $staff = User::factory()->create();
        $reason = ReasonForVisit::first();

        $response = $this->post(route('visitor.checkin'), [
            'mode' => 'existing',
            'visitor_id' => $visitor->id,
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
        ]);

        $this->assertDatabaseHas('visits', [
            'visitor_id' => $visitor->id,
            'staff_visited_id' => $staff->id,
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('visitor.success'));
    }
}
