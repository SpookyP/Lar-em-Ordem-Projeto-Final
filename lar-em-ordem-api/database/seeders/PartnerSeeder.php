<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\User\Partner;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Partner::factory(10)->create();
    }
}