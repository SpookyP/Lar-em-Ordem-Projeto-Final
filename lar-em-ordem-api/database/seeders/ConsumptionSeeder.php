<?php

namespace Database\Seeders;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\ConsumptionItem;
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
        $units = DB::table('consumption_types')->pluck('unit_of_measure', 'id'); // unidade de cada tipo

        Invoice::query()->lazy()->each(function ($invoice) use ($consumptionTypeIds, $commonTypeId, $recentLimit, $units) {
            $isRecent = Carbon::parse($invoice->period_start)->gte($recentLimit);

            $typeIds = $isRecent ? $consumptionTypeIds : collect([$commonTypeId]);

            foreach ($typeIds as $typeId) {
                $consumption = Consumption::factory()->create([
                    'invoice_id'          => $invoice->id,
                    'property_id'         => $invoice->property_id,
                    'consumption_type_id' => $typeId,
                    'period_start'        => $invoice->period_start,
                    'period_end'          => $invoice->period_end,
                ]);

                // Gerar sub-itens simulados proporcionais ao custo total do consumo
                ConsumptionItem::factory()->create([
                    'consumption_id' => $consumption->id,
                    'name'           => 'Consumo Principal',
                    'category'       => 'consumption',
                    'amount'         => $consumption->amount * 0.8,
                    'unit'           => $units[$typeId] ?? null,
                    'cost'           => round($consumption->cost * 0.7, 2),
                ]);

                ConsumptionItem::factory()->create([
                    'consumption_id' => $consumption->id,
                    'name'           => 'Termo Fixo / Potência',
                    'category'       => 'fixed_fee',
                    'amount'         => null,
                    'unit'           => null,
                    'cost'           => round($consumption->cost * 0.2, 2),
                ]);

                ConsumptionItem::factory()->create([
                    'consumption_id' => $consumption->id,
                    'name'           => 'IVA (6% / 23%)',
                    'category'       => 'tax',
                    'amount'         => null,
                    'unit'           => null,
                    'cost'           => round($consumption->cost * 0.1, 2),
                ]);
            }
        });
    }
}