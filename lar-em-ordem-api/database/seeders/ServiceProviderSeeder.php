<?php

namespace Database\Seeders;

use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Gives zones and specialties to the demo providers and creates three more providers,
     * so the Matosinhos requests get proposals from several providers.
     * Needs ServiceZoneSeeder and ProblemCategorySeeder.
     */
    public function run(): void
    {
        $matosinhos = ServiceZone::where('district', 'Porto')->where('county', 'Matosinhos')->pluck('id');
        $porto      = ServiceZone::where('district', 'Porto')->where('county', 'Porto')->pluck('id');

        // "Serviços Lda" (prestador@example.com)
        $servicos = User::where('email', 'prestador@example.com')->firstOrFail()->service_provider;
        $servicos->zones()->sync($matosinhos->merge($porto));
        $this->addSpecialties($servicos, ['Fugas de água', 'Desentupimentos', 'Esquentadores e termoacumuladores', 'Tomadas e iluminação']);

        // Other providers, with no demo account
        ServiceProviderModel::factory(3)->create()->each(function (ServiceProviderModel $provider) use ($matosinhos) {
            $provider->user->assignRole('service_provider');
            $provider->zones()->sync($matosinhos);
            $this->addSpecialties($provider, ['Fugas de água', 'Tomadas e iluminação', 'Tratamento de humidades']);
        });
    }
    /**
     * Gives the provider catalog specialties, looked up by name.
     *
     * @param ServiceProviderModel $provider The provider.
     * @param array<string> $names Specialty names in the catalog.
     */
    private function addSpecialties(ServiceProviderModel $provider, array $names): void
    {
        ServiceSpecialty::whereIn('name', $names)->get()->each(
            fn (ServiceSpecialty $specialty) => $provider->specialties()->create([
                'problem_category_id'  => $specialty->problem_category_id,
                'service_specialty_id' => $specialty->id,
            ])
        );
    }
}