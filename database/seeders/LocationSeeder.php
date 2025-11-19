<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Head Office
        $headOffice = Location::create([
            'name' => 'Head Office',
            'address' => '123 Corporate Drive, Lagos',
        ]);

        $groundFloor = Floor::create(['name' => 'Ground Floor', 'location_id' => $headOffice->id]);
        $firstFloor = Floor::create(['name' => '1st Floor', 'location_id' => $headOffice->id]);
        $secondFloor = Floor::create(['name' => '2nd Floor', 'location_id' => $headOffice->id]);

        Department::create(['name' => 'Reception', 'floor_id' => $groundFloor->id]);
        Department::create(['name' => 'Human Resources', 'floor_id' => $firstFloor->id]);
        Department::create(['name' => 'Finance', 'floor_id' => $firstFloor->id]);
        Department::create(['name' => 'IT', 'floor_id' => $secondFloor->id]);
        Department::create(['name' => 'Operations', 'floor_id' => $secondFloor->id]);

        // Branch A
        $branchA = Location::create([
            'name' => 'Branch A',
            'address' => '456 Business Avenue, Abuja',
        ]);

        $branchAGround = Floor::create(['name' => 'Ground Floor', 'location_id' => $branchA->id]);
        $branchAFirst = Floor::create(['name' => '1st Floor', 'location_id' => $branchA->id]);

        Department::create(['name' => 'Sales', 'floor_id' => $branchAGround->id]);
        Department::create(['name' => 'Customer Service', 'floor_id' => $branchAFirst->id]);

        // Branch B
        $branchB = Location::create([
            'name' => 'Branch B',
            'address' => '789 Commerce Street, Port Harcourt',
        ]);

        $branchBGround = Floor::create(['name' => 'Ground Floor', 'location_id' => $branchB->id]);

        Department::create(['name' => 'Marketing', 'floor_id' => $branchBGround->id]);
        Department::create(['name' => 'Administration', 'floor_id' => $branchBGround->id]);
    }
}
