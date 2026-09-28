<?php

namespace Database\Seeders;

use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

    //TEMPORARY CODE
        // 1. Property Types
        DB::table('property_types')->insertOrIgnore([
            ['id' => 1, 'type' => 'Apartment', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'type' => 'House', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Property Typologies
        DB::table('property_typologies')->insertOrIgnore([
            ['id' => 1, 'typology' => 'T1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'typology' => 'T2', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'typology' => 'T3', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Resident Types
        DB::table('resident_types')->insertOrIgnore([
            ['id' => 1, 'type' => 'Owner', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'type' => 'Tenant', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Default Address
        DB::table('addresses')->insertOrIgnore([
            [
                'id'          => 1,
                'street'      => 'Rua Principal',
                'postal_code' => '4000-000',
                'door'        => '100',
                'county'      => 'Porto',
                'location'    => 'Porto',
                'district'    => 'Porto',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // 5. Seed Roles & Seed User
        $this->call(RoleSeeder::class);
        $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
         ]);

         $this->call([
             ConsumptionTypeSeeder::class, 
        ]);

         $user->assignRole('SU');

        $this->call(PartnerSeeder::class);
    }
}