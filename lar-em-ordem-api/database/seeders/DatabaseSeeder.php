<?php

namespace Database\Seeders;

use App\Models\User\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RoleSeeder::class,
            PropertyTypeSeeder::class,
            PropertyTypologySeeder::class,
            ResidentTypeSeeder::class,
        ]);

        $this->call(RoleSeeder::class);
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->resident()->create([
            'name' => $user->name,
            'nif'  => '212345678',
        ]);
        $user->assignRole('resident');

        $this->call([
            PropertySeeder::class,
            PropertyContractSeeder::class,
            ResidentSeeder::class,
            PartnerSeeder::class,
            ConsumptionTypeSeeder::class,
            InvoiceSeeder::class,
            ConsumptionSeeder::class,
        ]);
    }
}
