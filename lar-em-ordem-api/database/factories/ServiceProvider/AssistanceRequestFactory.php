<?php

namespace Database\Factories\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\User\User;
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
            'user_id'             => User::factory(),
            'problem_category_id' => ProblemCategory::factory(),
            'title'               => fake()->sentence(4),
            'description'         => fake()->paragraph(),
            'status'              => 'open',
            'closed_at'           => null,
        ];
    }

    /**
     * Pedido em curso (o residente já aceitou uma proposta).
     */
    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }

    /**
     * Pedido concluído pelo residente.
     */
    public function closed(): static
    {
        return $this->state([
            'status'    => 'closed',
            'closed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Pedido cancelado pelo residente.
     */
    public function cancelled(): static
    {
        return $this->state([
            'status'    => 'cancelled',
            'closed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}