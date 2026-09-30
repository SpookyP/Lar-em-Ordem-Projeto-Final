<?php

namespace Database\Factories;

use App\Models\Invoice\Consumption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consumption>
 */
class ConsumptionFactory extends Factory
{
    protected $model = Consumption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumption_type_id' => $this->faker->numberBetween(1, 3),         
            'period_start' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'period_end' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'amount' => $this->faker->randomFloat(3, 1, 150), 
            'cost' => $this->faker->randomFloat(2, 10, 80),  
        ];
    }
}
