<?php

namespace Database\Factories;

use App\Models\Invoice\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

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
            'invoice_number'    =>      'FT-' . $this->faker->unique()->numerify('#####'),
            'issue_date'        =>      $start->copy()->addMonth()->addDays(10)->toDateString(),
            'period_start'      =>      $start->toDateString(),
            'period_end'        =>      $start->copy()->endOfMonth()->toDateString(),
            'total_amount'      =>      $this->faker->randomFloat(2, 20, 500),
            'supplier'          =>      $this->faker->company(),
            'file_path'         =>      null,
        ];
    }
}
