<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'image_url' => fake()->optional(0.3)->imageUrl(200, 200, 'people'),
            'is_blacklisted' => false,
            'reasons_for_blacklisting' => null,
            'date_blacklisted' => null,
            'blacklist_request_status' => null,
            'blacklist_request_reason' => null,
            'blacklist_requested_by' => null,
            'blacklist_requested_at' => null,
        ];
    }

    /**
     * Indicate that the visitor is blacklisted.
     */
    public function blacklisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blacklisted' => true,
            'reasons_for_blacklisting' => fake()->sentence(),
            'date_blacklisted' => fake()->dateTimeBetween('-1 year', 'now'),
            'blacklist_request_status' => 'approved',
        ]);
    }

    /**
     * Indicate that the visitor has a pending blacklist request.
     */
    public function blacklistRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blacklisted' => false,
            'blacklist_request_status' => 'pending',
            'blacklist_request_reason' => fake()->sentence(),
            'blacklist_requested_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
