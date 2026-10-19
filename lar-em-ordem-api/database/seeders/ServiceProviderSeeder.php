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
     * Dá zonas e especialidades aos prestadores de demonstração e cria mais três prestadores,
     * para que os pedidos de Matosinhos recebam propostas de vários prestadores.
     * Precisa do ServiceZoneSeeder e do ProblemCategorySeeder.
     */
    public function run(): void
    {
        $matosinhos = ServiceZone::where('district', 'Porto')->where('county', 'Matosinhos')->pluck('id');
        $porto      = ServiceZone::where('district', 'Porto')->where('county', 'Porto')->pluck('id');

        // "Serviços Lda" (service@example.com)
        $servicos = User::where('email', 'service@example.com')->firstOrFail()->service_provider;
        $servicos->zones()->sync($matosinhos->merge($porto));
        $this->addSpecialties($servicos, ['Fugas de água', 'Desentupimentos', 'Esquentadores e termoacumuladores', 'Tomadas e iluminação']);

        // "Serviçinhos Lda" (residente@example.com, que também é residente)
        $servicinhos = User::where('email', 'residente@example.com')->firstOrFail()->service_provider;
        $servicinhos->zones()->sync($matosinhos);
        $this->addSpecialties($servicinhos, ['Quadros elétricos', 'Tomadas e iluminação']);

        // Outros prestadores, sem conta de demonstração
        ServiceProviderModel::factory(3)->create()->each(function (ServiceProviderModel $provider) use ($matosinhos) {
            $provider->user->assignRole('service_provider');
            $provider->zones()->sync($matosinhos);
            $this->addSpecialties($provider, ['Fugas de água', 'Tomadas e iluminação', 'Tratamento de humidades']);
        });
    }
    /**
     * Associa ao prestador especialidades do catálogo, procuradas pelo nome.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array<string> $names Nomes das especialidades no catálogo.
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