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
        return [
            'invoice_number' => 'FT-' . $this->faker->unique()->numerify('#####'),
            'issue_date' => $this->faker->date(),
            'period_start' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'period_end' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'total_amount' => $this->faker->randomFloat(2, 20, 500), 
            'supplier' => $this->faker->company(),
            'file_path' => 'invoices/sample.pdf', 
        ];
    }
}
