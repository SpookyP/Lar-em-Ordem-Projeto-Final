<?php

namespace Database\Seeders;

use App\Models\User\User;
use App\Models\Property\PropertyContract;
use App\Models\User\ResidentType;
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
            DocumentCategorySeeder::class,
            CondominiumDocumentCategorySeeder::class,
            OfferCategorySeeder::class,
        ]);

        $morador = User::factory()->create([
            'name' => 'morador',
            'email' => 'morador@example.com',
        ]);
        $morador->resident()->create([
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
            'nif'  => '123456789',
        ]);
        $resident->assignRole('resident');
        PropertyContract::class::factory()->count(3)->sequence(
            ['resident_type_id' => ResidentType::where('type', 'Owner')->value('id')],
            ['resident_type_id' => ResidentType::where('type', 'Tenant')->value('id')],
        )->create([
            'resident_id' => $resident->resident->id,
        ]);
        $resident->service_provider()->create([
            "company_name" => "Serviçinhos Lda",
            "nif" => "212345674",
            "phone" => "912345679",
            "provider_email" => "general@servicos.pt",
            "description" => "Prestação de serviços de manutenção."
        ]);
        $resident->assignRole('service_provider');


        $partner = User::factory()->create([
            'name' => 'parceiro',
            'email' => 'parceiro@example.com',
        ]);
        $partner->partner()->create([
            'company_name' => 'Parceiro Lda',
            'nif'  => '123456789',
            "phone" => "912345678",
            "website" => "https://parceiro.pt",
            "description" => "Empresa parceira de gestão imobiliária."
        ]);
        $partner->assignRole('partner');

        $serviceProvider = User::factory()->create([
            'name' => 'prestador',
            'email' => 'prestador@example.com',
        ]);
        $serviceProvider->service_provider()->create([
            "company_name" => "Serviços Lda",
            "nif" => "212345673",
            "phone" => "912345678",
            "provider_email" => "geral@servicos.pt",
            "description" => "Prestação de serviços de manutenção."
        ]);
        $serviceProvider->assignRole('service_provider');


        $this->call([
            PropertySeeder::class,
            ResidentSeeder::class,
            PropertyContractSeeder::class,
            PartnerSeeder::class,
            ConsumptionTypeSeeder::class,
            InvoiceSeeder::class,
            ConsumptionSeeder::class,
        ]);

        $this->call(ServiceZoneSeeder::class);
        $this->call(ProblemCategorySeeder::class);

         // Modules 5, 6 and 7: they need the zones, categories and partners created above
        $this->call([
            ServiceProviderSeeder::class,
            AssistanceRequestSeeder::class,
            CondominiumSeeder::class,
            OfferSeeder::class,
        ]);

        $demo = ! app()->runningUnitTests();

        if ($demo) {
            $this->call(DemoSeeder::class);
        }
    
        // after every seeder that creates users: everyone accepts the policy and shares data
        $this->call(UserConsentSeeder::class);

        // last: benchmarks only count homes whose resident shares data, so the consents must exist first
        Artisan::call('benchmarks:backfill');
        $this->command->info(Artisan::output());

        $this->callWith(ResidenteConsumptionSeeder::class, ['allHomes' => $demo]);

        if ($demo) {
            $this->callWith(ResidenteConsumptionSeeder::class, ['email' => 'morador@example.com', 'allHomes' => true]);
        }
    }
}
