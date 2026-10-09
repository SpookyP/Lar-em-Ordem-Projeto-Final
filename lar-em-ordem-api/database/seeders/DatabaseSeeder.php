<?php

namespace Database\Seeders;

use App\Models\User\User;
use App\Models\Property\PropertyContract;
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

        $morador = User::factory()->create([
            'name' => 'morador',
            'email' => 'morador@example.com',
        ]);
        $morador->resident()->create([
            'name' => $morador->name,
            'nif'  => '123456789',
        ]);
        $morador->assignRole('resident');
        PropertyContract::class::factory()->count(1)->create([
            'resident_id' => $morador->resident->id,
        ]);

        $resident = User::factory()->create([
            'name' => 'residente',
            'email' => 'residente@example.com',
        ]);
        $resident->resident()->create([
            'name' => $resident->name,
            'nif'  => '123456789',
        ]);
        $resident->assignRole('resident');
        PropertyContract::class::factory()->count(6)->create([
            'resident_id' => $resident->resident->id,
        ]);

        $partner = User::factory()->create([
            'name' => 'parceiro',
            'email' => 'parceiro@example.com',
        ]);
        $partner->partner()->create([
            'name' => $partner->name,
            'nif'  => '123456789',
            "phone" => "912345678",
            "website" => "https://parceiro.pt",
            "description" => "Empresa parceira de gestão imobiliária."
        ]);
        $partner->assignRole('partner');

        $serviceProvider = User::factory()->create([
            'name' => 'service',
            'email' => 'service@example.com',
        ]);
        $serviceProvider->service_provider()->create([
            "company_name" => "Serviços Lda",
            "nif" => "212345673",
            "phone" => "912345678",
            "email" => "geral@servicos.pt",
            "description" => "Prestação de serviços de manutenção."
        ]);
        $serviceProvider->assignRole('service_provider');


        $this->call([
            PropertySeeder::class,
            PropertyContractSeeder::class,
            ResidentSeeder::class,
            PartnerSeeder::class,
            ConsumptionTypeSeeder::class,
            InvoiceSeeder::class,
            ConsumptionSeeder::class,
        ]);

        $this->call(ServiceZoneSeeder::class);

        // Rodar o script de Benchmark_Backfill automaticamente para criar os Benchmarks de mêses passados
        Artisan::call('benchmarks:backfill');
        $this->command->info(Artisan::output());
    }
}
