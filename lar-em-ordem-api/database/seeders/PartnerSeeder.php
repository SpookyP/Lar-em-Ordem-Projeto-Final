<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('partners')->insert([
            [
                'user_id' => 1, 
                'name' => 'Tech Solutions Lda',
                'nif' => '501234567',
                'phone' => '+351912345678',
                'website' => 'https://techsolutions.pt',
                'description' => 'Empresa focada no desenvolvimento de software e infraestruturas cloud.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}