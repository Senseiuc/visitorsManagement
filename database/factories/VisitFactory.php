<?php

namespace Database\Factories;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'staff_visited_id' => User::factory(),
            'reason_for_visit_id' => ReasonForVisit::factory(),
            'status' => 'pending',
            'checkin_time' => null,
            'checkout_time' => null,
            'tag_number' => fake()->optional()->numerify('TAG-###'),
        ];
    }

    /**
     * Indicate that the visit is approved and checked in.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'checkin_time' => fake()->dateTimeBetween('-8 hours', 'now'),
            'tag_number' => fake()->numerify('TAG-###'),
        ]);
    }

    /**
     * Indicate that the visit is approved and checked out.
     */
    public function checkedOut(): static
    {
        $checkinTime = fake()->dateTimeBetween('-8 hours', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'checkin_time' => $checkinTime,
            'checkout_time' => fake()->dateTimeBetween($checkinTime, 'now'),
            'tag_number' => fake()->numerify('TAG-###'),
        ]);
    }

    /**
     * Indicate that the visit is currently on-site (approved but not checked out).
     */
    public function onSite(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'checkin_time' => fake()->dateTimeBetween('-8 hours', 'now'),
            'checkout_time' => null,
            'tag_number' => fake()->numerify('TAG-###'),
        ]);
    }
}
