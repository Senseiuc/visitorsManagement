<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Head Office',
                'Branch Office - Lagos',
                'Branch Office - Abuja',
                'Branch Office - Port Harcourt',
                'Regional Office - North',
                'Regional Office - South',
            ]),
            'address' => fake()->streetAddress() . ', ' . fake()->city(),
        ];
    }
}
