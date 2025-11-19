<?php

namespace Tests\Unit;

use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_accessor_concatenates_names(): void
    {
        $visitor = Visitor::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $visitor->full_name);
    }

    public function test_blacklisted_visitor_has_correct_attributes(): void
    {
        $visitor = Visitor::factory()->blacklisted()->create();

        $this->assertTrue($visitor->is_blacklisted);
        $this->assertNotNull($visitor->reasons_for_blacklisting);
        $this->assertNotNull($visitor->date_blacklisted);
        $this->assertEquals('approved', $visitor->blacklist_request_status);
    }

    public function test_visitor_with_blacklist_request_has_pending_status(): void
    {
        $visitor = Visitor::factory()->blacklistRequested()->create();

        $this->assertFalse($visitor->is_blacklisted);
        $this->assertEquals('pending', $visitor->blacklist_request_status);
        $this->assertNotNull($visitor->blacklist_request_reason);
        $this->assertNotNull($visitor->blacklist_requested_at);
    }

    public function test_visitor_image_url_can_be_null(): void
    {
        $visitor = Visitor::factory()->create([
            'image_url' => null,
        ]);

        $this->assertNull($visitor->image_url);
    }

    public function test_visitor_can_have_visits_relationship(): void
    {
        $visitor = Visitor::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $visitor->visits);
    }
}
