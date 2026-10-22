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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->numberBetween(8, 15);

        return [
            'assistance_request_id' => AssistanceRequest::factory(),
            'service_provider_id'   => ServiceProviderModel::factory(),
            'estimated_value'       => fake()->randomFloat(2, 30, 800),
            'description'           => fake()->sentence(),
            'validity'              => now()->addDays(15)->toDateString(),
            'available_date'        => now()->addDays(fake()->numberBetween(1, 10))->toDateString(),
            'available_from'        => sprintf('%02d:00', $hour),
            'available_until'       => sprintf('%02d:00', $hour + 3),
            'status'                => 'pending',
            'response_date'         => null,
        ];
    }

    /**
     * Proposta aceite pelo residente.
     */
    public function accepted(): static
    {
        return $this->state([
            'status'        => 'accepted',
            'response_date' => now(),
        ]);
    }

    /**
     * Proposta recusada pelo residente.
     */
    public function rejected(): static
    {
        return $this->state([
            'status'        => 'rejected',
            'response_date' => now(),
        ]);
    }

    /**
     * Proposta retirada pelo prestador.
     */
    public function withdrawn(): static
    {
        return $this->state(['status' => 'withdrawn']);
    }
}