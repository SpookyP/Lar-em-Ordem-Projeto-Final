<?php

namespace Database\Seeders;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsumptionSeeder extends Seeder
{
    public function run(): void
    {
        $consumptionTypeIds = DB::table('consumption_types')->orderBy('id')->pluck('id');

        if ($consumptionTypeIds->isEmpty()) {
            return;
        }

        // Primeiro tipo em todas as faturas; os restantes só nos últimos 3 meses.
        $commonTypeId = $consumptionTypeIds->first();
        $recentLimit = now()->startOfMonth()->subMonths(3);

        Invoice::query()->lazy()->each(function (Invoice $invoice) use ($consumptionTypeIds, $commonTypeId, $recentLimit) {
            $isRecent = Carbon::parse($invoice->period_start)->gte($recentLimit);

            $typeIds = $isRecent ? $consumptionTypeIds : collect([$commonTypeId]);

            foreach ($typeIds as $typeId) {
                Consumption::factory()->create([
                    'invoice_id'          => $invoice->id,
                    'property_id'         => $invoice->property_id,
                    'consumption_type_id' => $typeId,
                    'period_start'        => $invoice->period_start,
                    'period_end'          => $invoice->period_end,
                ]);
            }
        });
    }
}
