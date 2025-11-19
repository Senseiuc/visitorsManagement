<?php

namespace Tests\Feature;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\VisitCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VisitNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_when_visit_created(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'phone_number' => '+2348012345678',
        ]);
        $visitor = Visitor::factory()->create();
        $reason = ReasonForVisit::factory()->create();

        $visit = Visit::create([
            'visitor_id' => $visitor->id,
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
            'status' => 'pending',
        ]);

        Notification::assertSentTo(
            $staff,
            VisitCreatedNotification::class,
            function ($notification) use ($visit) {
                return $notification->visit->id === $visit->id;
            }
        );
    }

    public function test_notification_not_sent_when_no_staff_assigned(): void
    {
        Notification::fake();

        $visitor = Visitor::factory()->create();
        $reason = ReasonForVisit::factory()->create();

        Visit::create([
            'visitor_id' => $visitor->id,
            'staff_visited_id' => null,
            'reason_for_visit_id' => $reason->id,
            'status' => 'pending',
        ]);

        Notification::assertNothingSent();
    }

    public function test_notification_contains_correct_visit_details(): void
    {
        $staff = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'staff@example.com',
            'phone_number' => '+2348012345678',
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $reason = ReasonForVisit::factory()->create(['name' => 'Business Meeting']);

        $visit = Visit::factory()->create([
            'visitor_id' => $visitor->id,
            'staff_visited_id' => $staff->id,
            'reason_for_visit_id' => $reason->id,
        ]);

        $notification = new VisitCreatedNotification($visit);

        // Test email content
        $mailMessage = $notification->toMail($staff);
        $this->assertStringContainsString('Jane Smith', $mailMessage->render());

        // Test SMS content
        $smsData = $notification->toTermii($staff);
        $this->assertEquals($staff->phone_number, $smsData['to']);
        $this->assertStringContainsString('Jane Smith', $smsData['message']);
        $this->assertStringContainsString('Business Meeting', $smsData['message']);
    }
}
