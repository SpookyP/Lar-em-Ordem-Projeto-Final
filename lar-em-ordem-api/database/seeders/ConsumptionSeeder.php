<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Factories\Sequence;
use App\Models\Invoice\Consumption;
use App\Models\Invoice\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsumptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();

        $typeIds = DB::table('consumption_types')->pluck('id')->toArray();

        if ($invoices->isEmpty()) {
            return;
        }

        $sequence = array_map(function ($id) {
            return ['consumption_type_id' => $id];
        }, $typeIds);

        foreach ($invoices as $invoice) {
            Consumption::factory()
                ->count(count($typeIds)) 
                ->state(new Sequence(...$sequence))
                ->create([
                    'invoice_id'    =>   $invoice->id,
                    'property_id'   =>   $invoice->property_id,
                    'period_start'  =>   $invoice->period_start,
                    'period_end'    =>   $invoice->period_end,
                ]);
        }
    }
}