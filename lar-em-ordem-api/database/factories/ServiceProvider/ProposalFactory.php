<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /** Realistic proposal descriptions. */
    private const DESCRIPTIONS = [
        'Deslocação, diagnóstico e reparação, material incluído.',
        'Visita técnica e orçamento final no local.',
        'Substituição da peça avariada e teste do funcionamento.',
        'Reparação com garantia de 6 meses.',
        'Mão de obra e material, com limpeza no fim do trabalho.',
        'Intervenção num só dia, com marcação à escolha do cliente.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->numberBetween(8, 15);

        // validity goes up to the available date at most
        $availableDays = fake()->numberBetween(5, 15);

        return [
            'assistance_request_id' => AssistanceRequest::factory(),
            'service_provider_id'   => ServiceProviderModel::factory(),
            'estimated_value'       => fake()->randomFloat(2, 30, 800),
            'description'           => fake()->randomElement(self::DESCRIPTIONS),
            'validity'              => now()->addDays(fake()->numberBetween(1, $availableDays))->toDateString(),
            'available_date'        => now()->addDays($availableDays)->toDateString(),
            'available_from'        => sprintf('%02d:00', $hour),
            'available_until'       => sprintf('%02d:00', $hour + 3),
            'status'                => 'pending',
            'response_date'         => null,
        ];
    }

    /**
     * Proposal accepted by the resident.
     */
    public function accepted(): static
    {
        return $this->state([
            'status'        => 'accepted',
            'response_date' => now(),
        ]);
    }

    /**
     * Proposal rejected by the resident.
     */
    public function rejected(): static
    {
        return $this->state([
            'status'        => 'rejected',
            'response_date' => now(),
        ]);
    }

    /**
     * Proposal withdrawn by the provider.
     */
    public function withdrawn(): static
    {
        return $this->state(['status' => 'withdrawn']);
    }
}