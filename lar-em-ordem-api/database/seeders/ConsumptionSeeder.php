<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsumptionSeeder extends Seeder
{
    private const CHUNK = 500;

    public function run(): void
    {
        $consumptionTypeIds = DB::table('consumption_types')->orderBy('id')->pluck('id');

        if ($consumptionTypeIds->isEmpty()) {
            return;
        }

        // First type on every invoice; the other types only on the last 3 months.
        $commonTypeId = $consumptionTypeIds->first();
        $recentLimit = now()->startOfMonth()->subMonths(3)->toDateString();
        $units = DB::table('consumption_types')->pluck('unit_of_measure', 'id'); // unit of each type
        $now = now();

        $consumptions = [];
        $items = [];

        // plain inserts in chunks: going through the models and factories means 4+ queries per consumption
        foreach (DB::table('invoices')->select('id', 'property_id', 'period_start', 'period_end')->cursor() as $invoice) {
            $isRecent = substr($invoice->period_start, 0, 10) >= $recentLimit;
            $typeIds = $isRecent ? $consumptionTypeIds : collect([$commonTypeId]);

            foreach ($typeIds as $typeId) {
                $id = (string) Str::ulid();
                $amount = round(mt_rand(100000, 300000) / 1000, 3);
                $cost = round(mt_rand(1000, 8000) / 100, 2);

                $consumptions[] = [
                    'id'                  => $id,
                    'property_id'         => $invoice->property_id,
                    'invoice_id'          => $invoice->id,
                    'consumption_type_id' => $typeId,
                    'period_start'        => $invoice->period_start,
                    'period_end'          => $invoice->period_end,
                    'amount'              => $amount,
                    'cost'                => $cost,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];

                // simulated sub-items, proportional to the consumption cost
                $items[] = $this->item($id, 'Consumo Principal', 'consumption', round($amount * 0.8, 2), $units[$typeId] ?? null, round($cost * 0.7, 2), $now);
                $items[] = $this->item($id, 'Termo Fixo / Potência', 'fixed_fee', null, null, round($cost * 0.2, 2), $now);
                $items[] = $this->item($id, 'IVA (6% / 23%)', 'tax', null, null, round($cost * 0.1, 2), $now);
            }

            if (count($consumptions) >= self::CHUNK) {
                $this->flush($consumptions, $items);
            }
        }

        $this->flush($consumptions, $items);
    }

    private function item(string $consumptionId, string $name, string $category, ?float $amount, ?string $unit, float $cost, $now): array
    {
        return [
            'consumption_id' => $consumptionId,
            'name'           => $name,
            'category'       => $category,
            'amount'         => $amount,
            'unit'           => $unit,
            'cost'           => $cost,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
    }

    // consumptions go first: the items point at them
    private function flush(array &$consumptions, array &$items): void
    {
        if ($consumptions !== []) {
            DB::table('consumptions')->insert($consumptions);
            DB::table('consumption_items')->insert($items);
        }

        $consumptions = [];
        $items = [];
    }
}