<?php

namespace Database\Factories;

use App\Models\Invoice\ConsumptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumptionItem>
 */
class ConsumptionItemFactory extends Factory
{
    protected $model = ConsumptionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          =>      $this->faker->randomElement(['Energia consumida', 'Termo fixo', 'IVA', 'Taxas de exploração']),
            'category'      =>      $this->faker->randomElement(['consumption', 'fixed_fee', 'tax']),
            'amount'        =>      $this->faker->optional()->randomFloat(2, 10, 150),
            'unit'          =>      $this->faker->optional()->randomElement(['kWh', 'm³', 'kVA']),
            'cost'          =>      $this->faker->randomFloat(2, 5, 80),
        ];
    }
}