<?php

namespace Database\Seeders;

use App\Models\ReasonForVisit;
use Illuminate\Database\Seeder;

class ReasonForVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            ['name' => 'Business Meeting'],
            ['name' => 'Job Interview'],
            ['name' => 'Delivery'],
            ['name' => 'Maintenance'],
            ['name' => 'Consultation'],
            ['name' => 'Training'],
            ['name' => 'Site Inspection'],
            ['name' => 'Client Visit'],
            ['name' => 'Vendor Meeting'],
            ['name' => 'Other'],
        ];

        foreach ($reasons as $reason) {
            ReasonForVisit::create($reason);
        }
    }
}
