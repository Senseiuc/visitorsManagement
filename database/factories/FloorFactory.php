<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Floor>
 */
class FloorFactory extends Factory
{
    protected $model = Floor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Ground Floor',
                '1st Floor',
                '2nd Floor',
                '3rd Floor',
                '4th Floor',
                'Basement',
            ]),
            'location_id' => Location::factory(),
        ];
    }
}
