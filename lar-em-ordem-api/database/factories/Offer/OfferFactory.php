<?php

namespace Database\Factories\Offer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state (oferta em vigor).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_id'  => Partner::factory(),
            'title'       => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type'        => fake()->randomElement(['Desconto', 'Seguro', 'Mudanças', 'Limpeza', 'Energia']),
            'url'         => fake()->optional()->url(),
            'start_date'  => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'end_date'    => now()->addDays(fake()->numberBetween(15, 90))->toDateString(),
            'active'      => true,
        ];
    }

    /**
     * Oferta que já terminou.
     */
    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date'   => now()->subMonth()->toDateString(),
        ]);
    }

    /**
     * Oferta que ainda não começou.
     */
    public function upcoming(): static
    {
        return $this->state([
            'start_date' => now()->addWeeks(2)->toDateString(),
            'end_date'   => now()->addMonths(2)->toDateString(),
        ]);
    }

    /**
     * Oferta desativada pelo parceiro.
     */
    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}