<?php

namespace Database\Seeders;

use App\Models\User\Resident;
use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Resident::factory(10)->create();
    }
}
