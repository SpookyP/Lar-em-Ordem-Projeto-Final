<?php

namespace Database\Seeders;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\Invoice;
use Illuminate\Database\Seeder;

class ConsumptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();

        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $invoice) {
            Consumption::factory()->count(4)->create([
                'invoice_id' => $invoice->id,
                'property_id' => $invoice->property_id,
            ]);
        }
    }
}