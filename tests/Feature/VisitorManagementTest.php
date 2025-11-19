<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\RolePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePresetSeeder::class);
    }

    public function test_visitor_can_be_created(): void
    {
        $visitor = Visitor::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile' => '+234 801 234 5678',
        ]);

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->assertInstanceOf(Visitor::class, $visitor);
    }

    public function test_visitor_can_be_updated(): void
    {
        $visitor = Visitor::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $visitor->update([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_visitor_full_name_accessor_works(): void
    {
        $visitor = Visitor::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $visitor->full_name);
    }

    public function test_visitor_image_url_can_be_set(): void
    {
        $visitor = Visitor::factory()->create([
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertEquals('https://example.com/image.jpg', $visitor->image_url);
    }

    public function test_visitor_can_be_deleted(): void
    {
        $visitor = Visitor::factory()->create();
        $visitorId = $visitor->id;

        $visitor->delete();

        $this->assertDatabaseMissing('visitors', [
            'id' => $visitorId,
        ]);
    }

    public function test_visitor_has_visits_relationship(): void
    {
        $visitor = Visitor::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $visitor->visits);
    }
}
