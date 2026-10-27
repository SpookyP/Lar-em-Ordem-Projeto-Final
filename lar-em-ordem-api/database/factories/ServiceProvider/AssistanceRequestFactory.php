<?php

namespace Database\Factories\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\User\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistanceRequest>
 */
class AssistanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id'         => Property::factory(),
            'resident_id'         => Resident::factory(),
            'problem_category_id' => ProblemCategory::factory(),
            'title'               => fake()->sentence(4),
            'description'         => fake()->paragraph(),
            'status'              => 'open',
            'closed_at'           => null,
        ];
    }

    /**
     * Pedido fechado (o residente aceitou uma proposta).
     */
    public function closed(): static
    {
        return $this->state(function () {
            $closedAt = now()->subDays(fake()->numberBetween(1, 30));

            return [
                'status'     => 'closed',
                'created_at' => $closedAt->copy()->subDays(fake()->numberBetween(1, 10)),
                'closed_at'  => $closedAt,
            ];
        });
    }

    /**
     * Pedido cancelado pelo residente.
     */
    public function cancelled(): static
    {
        return $this->state(function () {
            $closedAt = now()->subDays(fake()->numberBetween(1, 30));

            return [
                'status'     => 'cancelled',
                'created_at' => $closedAt->copy()->subDays(fake()->numberBetween(1, 10)),
                'closed_at'  => $closedAt,
            ];
        });
    }
}