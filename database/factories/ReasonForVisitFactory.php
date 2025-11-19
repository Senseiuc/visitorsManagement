<?php

namespace Database\Factories;

use App\Models\ReasonForVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReasonForVisit>
 */
class ReasonForVisitFactory extends Factory
{
    protected $model = ReasonForVisit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Business Meeting',
                'Job Interview',
                'Delivery',
                'Maintenance',
                'Consultation',
                'Training',
                'Site Inspection',
                'Client Visit',
            ]),
        ];
    }
}
