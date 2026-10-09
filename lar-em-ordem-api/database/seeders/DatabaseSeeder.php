<?php

namespace Database\Seeders;

use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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

        // Rodar o script de Benchmark_Backfill automaticamente para criar os Benchmarks de mêses passados
        Artisan::call('benchmarks:backfill');
        $this->command->info(Artisan::output());
    }
}
