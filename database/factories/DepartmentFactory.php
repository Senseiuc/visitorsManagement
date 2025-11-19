<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Human Resources',
                'Finance',
                'IT',
                'Operations',
                'Sales',
                'Marketing',
                'Customer Service',
                'Legal',
                'Administration',
            ]),
            'floor_id' => Floor::factory(),
        ];
    }
}
