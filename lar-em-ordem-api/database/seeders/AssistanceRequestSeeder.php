<?php

namespace Database\Seeders;

use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\ResidentType;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class AssistanceRequestSeeder extends Seeder
{
    /**
     * Creates a demo property in Matosinhos for morador@example.com and one assistance
     * request in each status, with their proposals.
     * Needs ServiceProviderSeeder (providers with zones and specialties).
     */
    public function run(): void
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        // Real address, inside the demo providers' zones
        $address = Address::create([
            'street'      => 'Rua Brito Capelo',
            'postal_code' => '4450-073',
            'door'        => 'Nº 153',
            'fraction'    => '2º Esq',
            'county'      => 'Matosinhos',
            'location'    => 'Matosinhos',
            'district'    => 'Porto',
        ]);
        $property = Property::factory()->create(['address_id' => $address->id]);
        PropertyContract::factory()->create([
            'resident_id' => $morador->resident->id,
            'property_id' => $property->id,
            'resident_type_id' => ResidentType::where('type', 'Owner')->value('id'),
            'is_active'   => true,
        ]);

        $servicos    = User::where('email', 'prestador@example.com')->firstOrFail()->service_provider;
        $servicinhos = User::where('email', 'residente@example.com')->firstOrFail()->service_provider;
        $outro       = ServiceProviderModel::whereKeyNot([$servicos->id, $servicinhos->id])->has('zones')->firstOrFail();

        $canalizacao  = ProblemCategory::where('name', 'Canalização')->value('id');
        $eletricidade = ProblemCategory::where('name', 'Eletricidade')->value('id');
        $pintura      = ProblemCategory::where('name', 'Pintura')->value('id');

        $base = ['property_id' => $property->id, 'resident_id' => $morador->resident->id];

        // 1. Open, no proposals: shows in "available" for the plumbing providers
        AssistanceRequest::factory()->create($base + [
            'problem_category_id' => $canalizacao,
            'title'               => 'Fuga de água debaixo do lava-loiça',
            'description'         => 'Está sempre a pingar e o armário já tem a madeira inchada.',
        ]);

        // 2. Open: two pending proposals (the resident can accept or reject them)
        //    and one rejected on its own (the request stays open)
        $tomada = AssistanceRequest::factory()->create($base + [
            'problem_category_id' => $eletricidade,
            'title'               => 'Tomada da sala sem corrente',
            'description'         => 'A tomada ao lado da televisão deixou de funcionar. O quadro não disparou.',
        ]);
        Proposal::factory()->create([
            'assistance_request_id' => $tomada->id,
            'service_provider_id'   => $servicos->id,
            'estimated_value'       => 45,
            'description'           => 'Substituição da tomada e verificação do circuito.',
        ]);
        Proposal::factory()->create([
            'assistance_request_id' => $tomada->id,
            'service_provider_id'   => $servicinhos->id,
            'estimated_value'       => 60,
            'description'           => 'Diagnóstico e reparação, material incluído.',
        ]);
        Proposal::factory()->rejected()->create([
            'assistance_request_id' => $tomada->id,
            'service_provider_id'   => $outro->id,
            'estimated_value'       => 150,
        ]);

        // 3. Closed: "Serviços Lda" proposal accepted, the other provider's rejected automatically
        $sanita = AssistanceRequest::factory()->closed()->create($base + [
            'problem_category_id' => $canalizacao,
            'title'               => 'Sanita entupida',
            'description'         => 'A água não escoa e já tentei com desentupidor.',
        ]);
        Proposal::factory()->accepted()->create([
            'assistance_request_id' => $sanita->id,
            'service_provider_id'   => $servicos->id,
            'estimated_value'       => 70,
            'description'           => 'Desentupimento com máquina.',
        ]);
        Proposal::factory()->rejected()->create([
            'assistance_request_id' => $sanita->id,
            'service_provider_id'   => $outro->id,
            'estimated_value'       => 95,
        ]);

        // 4. Closed last month, with a proposal the provider withdrew before it was accepted
        $esquentador = AssistanceRequest::factory()->closed()->create($base + [
            'problem_category_id' => $canalizacao,
            'title'               => 'Esquentador não aquece a água',
            'description'         => 'Acende mas a água sai fria passado um minuto.',
        ]);
        Proposal::factory()->accepted()->create([
            'assistance_request_id' => $esquentador->id,
            'service_provider_id'   => $servicos->id,
            'estimated_value'       => 120,
            'description'           => 'Limpeza do esquentador e substituição da membrana.',
            'validity'              => now()->subMonth()->addDays(3)->toDateString(),
            'available_date'        => now()->subMonth()->addDays(10)->toDateString(),
        ]);
    
        Proposal::factory()->withdrawn()->create([
            'assistance_request_id' => $esquentador->id,
            'service_provider_id'   => $servicinhos->id,
            'estimated_value'       => 140,
        ]);

        // 5. Cancelled: the proposal that was pending became rejected
        $humidade = AssistanceRequest::factory()->cancelled()->create($base + [
            'problem_category_id' => $pintura,
            'title'               => 'Mancha de humidade no teto do quarto',
            'description'         => 'Apareceu depois das chuvas, junto à janela.',
        ]);
        Proposal::factory()->rejected()->create([
            'assistance_request_id' => $humidade->id,
            'service_provider_id'   => $outro->id,
            'estimated_value'       => 250,
        ]);
    }
}