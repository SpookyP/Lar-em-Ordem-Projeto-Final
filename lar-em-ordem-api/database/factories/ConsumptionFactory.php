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
        $month = $this->faker->numberBetween(1, 9);
        $start = now()->startOfYear()->addMonths($month - 1);

        return [
            'consumption_type_id'   =>      1,
            'period_start'          =>      $start->toDateString(),
            'period_end'            =>      $start->copy()->endOfMonth()->toDateString(),
            'amount'                =>      $this->faker->randomFloat(3, 100, 300),
            'cost'                  =>      $this->faker->randomFloat(2, 10, 80),
        ];
    }
}
