<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\ConsumptionBenchmark;
use App\Models\Invoice\ConsumptionType;
use App\Models\Property\Property;
use Carbon\Carbon;

class BenchmarkService
{
    /**
     * Devolve a série histórica mensal de custos médios do grupo comparável.
     *
     * @return array<int, array<string, mixed>>
     */
    public function monthlyCostSeries(Property $property): array
    {
        $property->loadMissing(['address', 'propertyType', 'propertyTypology']);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ConsumptionBenchmark> $benchmarks */
        $benchmarks = ConsumptionBenchmark::query()
            ->where('property_type_id', $property->property_type_id)
            ->where('typology_id', $property->property_typology_id)
            ->where('region', $property->address?->district)
            ->where('reference_period', 'monthly')
            ->orderBy('period_start')
            ->orderBy('consumption_type_id')
            ->get([
                'consumption_type_id',
                'period_start',
                'average_cost',
                'sample_size',
            ]);

        return $benchmarks
            ->map(fn (ConsumptionBenchmark $benchmark) => [
                'consumption_type_id' => (int) $benchmark->consumption_type_id,
                'period_start' => Carbon::parse($benchmark->period_start)->toDateString(),
                'average_cost' => $benchmark->average_cost !== null
                    ? round((float) $benchmark->average_cost, 2)
                    : null,
                'sample_size' => (int) $benchmark->sample_size,
            ])
            ->values()
            ->all();
    }

    /**
     * Calcula o número de dias entre duas datas.
     */
    private function days(Carbon $a, Carbon $b): int
    {
        return (int) abs($a->copy()->startOfDay()->diffInDays($b->copy()->startOfDay()));
    }

    /**
     * Devolve a comparação anual de todos os serviços numa única resposta.
     * @return array<string, mixed>
     */
    public function annualCostComparison(Property $property): array
    {
        $property->loadMissing(['address']);

        $ids = [1, 2, 3];
        $currentYear = now()->year;
        $benchmarkEstimatedMonths = max(0, 12 - (now()->month - 1));

        $benchmarks = ConsumptionBenchmark::query()
            ->where('property_type_id', $property->property_type_id)
            ->where('typology_id', $property->property_typology_id)
            ->where('region', $property->address?->district)
            ->where('reference_period', 'annual')
            ->whereNotNull('average_cost')
            ->whereIn('consumption_type_id', $ids)
            ->get([
                'consumption_type_id',
                'period_start',
                'average_cost',
                'sample_size',
            ]);

        /** @var array<int, array<int, array{cost: float, sample_size: ?int, estimated: bool, estimated_months_count: int}>> $benchmarkByYear */
        $benchmarkByYear = [];

        foreach ($benchmarks as $benchmark) {
            $year = Carbon::parse($benchmark->period_start)->year;
            $typeId = (int) $benchmark->consumption_type_id;
            $isEstimated = $year === $currentYear;

            $benchmarkByYear[$year][$typeId] = [
                'cost' => (float) $benchmark->average_cost,
                'sample_size' => $benchmark->sample_size !== null
                    ? (int) $benchmark->sample_size
                    : null,
                'estimated' => $isEstimated,
                'estimated_months_count' => $isEstimated
                    ? $benchmarkEstimatedMonths
                    : 0,
            ];
        }

        if (!$benchmarkByYear) {
            return [
                'reference_period' => 'annual',
                'service' => 'all',
                'service_options' => [],
                'data' => [],
                'services' => new \stdClass(),
            ];
        }

        $first = Carbon::create(min(array_keys($benchmarkByYear)), 1, 1);
        $last = Carbon::create(max(array_keys($benchmarkByYear)), 12, 31);

        $consumptions = Consumption::query()
            ->where('property_id', $property->id)
            ->whereIn('consumption_type_id', $ids)
            ->whereNotNull('cost')
            ->whereDate('period_start', '<=', $last->toDateString())
            ->whereDate('period_end', '>=', $first->toDateString())
            ->get(['consumption_type_id', 'period_start', 'period_end', 'cost']);

        /** @var array<int, array<int, array<string, float>>> $monthly */
        $monthly = [];

        foreach ($consumptions as $consumption) {
            $start = Carbon::parse($consumption->period_start);
            $end = Carbon::parse($consumption->period_end);

            if ($end->lt($start)) {
                continue;
            }

            $totalDays = $this->days($start, $end) + 1;
            $type = (int) $consumption->consumption_type_id;
            $cost = (float) $consumption->cost;

            for ($month = $start->copy()->startOfMonth(); $month->lte($end); $month->addMonth()) {
                $from = $start->gt($month->copy()->startOfMonth())
                    ? $start
                    : $month->copy()->startOfMonth();

                $to = $end->lt($month->copy()->endOfMonth())
                    ? $end
                    : $month->copy()->endOfMonth();

                $days = $this->days($from, $to) + 1;
                $key = $month->format('Y-m');

                $monthly[$month->year][$type][$key] =
                    ($monthly[$month->year][$type][$key] ?? 0)
                    + ($cost * $days / $totalDays);
            }
        }

        /** @var array<int, array<int, array<string, mixed>>> $eligible */
        $eligible = [];

        foreach ($benchmarkByYear as $year => $servicesForYear) {
            foreach ($ids as $id) {
                if (!isset($servicesForYear[$id])) {
                    continue;
                }

                $months = $monthly[$year][$id] ?? [];
                $count = count($months);

                if ($count < 9) {
                    continue;
                }

                $total = array_sum($months);
                $missing = 12 - $count;
                $benchmark = $servicesForYear[$id];
                $userTotal = round($total + ($total / $count * $missing), 2);
                $benchmarkTotal = round($benchmark['cost'], 2);
                $difference = round($userTotal - $benchmarkTotal, 2);

                $eligible[$year][$id] = [
                    'consumption_type_id' => $id,
                    'consumption_type' => null,
                    'user_total' => $userTotal,
                    'benchmark_total' => $benchmarkTotal,
                    'difference' => $difference,
                    'difference_percentage' => $benchmarkTotal > 0
                        ? round($difference / $benchmarkTotal * 100, 2)
                        : null,
                    'months_count' => $count,
                    'estimated_months_count' => $missing,
                    'benchmark_estimated' => $benchmark['estimated'],
                    'benchmark_estimated_months_count' => $benchmark['estimated_months_count'],
                    'sample_size' => $benchmark['sample_size'],
                ];
            }
        }

        $types = ConsumptionType::whereIn('id', $ids)
            ->get(['id', 'name'])
            ->keyBy('id');

        // Acrescenta o nome do serviço a cada série para a interface poder
        // apresentar as estimativas por serviço sem fazer pedidos adicionais.
        foreach ($eligible as $year => $servicesForYear) {
            foreach ($servicesForYear as $id => $row) {
                $eligible[$year][$id]['consumption_type'] = $types->get($id)?->name;
            }
        }

        $serviceData = [];
        $available = [];

        foreach ($ids as $id) {
            $rows = [];

            foreach ($eligible as $year => $servicesForYear) {
                if (!isset($servicesForYear[$id])) {
                    continue;
                }

                $row = $servicesForYear[$id];
                $rows[] = [
                    'year' => (int) $year,
                    'user_total' => $row['user_total'],
                    'benchmark_total' => $row['benchmark_total'],
                    'difference' => $row['difference'],
                    'difference_percentage' => $row['difference_percentage'],
                    'months_count' => $row['months_count'],
                    'estimated_months_count' => $row['estimated_months_count'],
                    'benchmark_estimated' => $row['benchmark_estimated'],
                    'benchmark_estimated_months_count' => $row['benchmark_estimated_months_count'],
                    'sample_size' => $row['sample_size'],
                ];
            }

            usort($rows, fn ($a, $b) => $b['year'] <=> $a['year']);

            if ($rows !== []) {
                $available[] = $id;
                $serviceData[(string) $id] = [
                    'value' => (string) $id,
                    'label' => $types->get($id)?->name,
                    'data' => $rows,
                ];
            }
        }

        // A vista Geral só inclui anos em que os três serviços são elegíveis.
        $generalData = [];

        foreach ($eligible as $year => $servicesForYear) {
            if (count(array_intersect($ids, array_keys($servicesForYear))) !== 3) {
                continue;
            }

            $serviceDetails = [];
            $userTotal = 0.0;
            $benchmarkTotal = 0.0;
            $estimatedServices = [];

            foreach ($ids as $id) {
                $serviceRow = $servicesForYear[$id];
                $userTotal += $serviceRow['user_total'];
                $benchmarkTotal += $serviceRow['benchmark_total'];

                $detail = [
                    'consumption_type_id' => $id,
                    'consumption_type' => $types->get($id)?->name,
                    'estimated_months_count' => $serviceRow['estimated_months_count'],
                    'benchmark_estimated' => $serviceRow['benchmark_estimated'],
                    'benchmark_estimated_months_count' => $serviceRow['benchmark_estimated_months_count'],
                    'sample_size' => $serviceRow['sample_size'],
                ];

                $serviceDetails[] = $detail;

                if ($detail['estimated_months_count'] > 0 || $detail['benchmark_estimated']) {
                    $estimatedServices[] = $detail;
                }
            }

            $userTotal = round($userTotal, 2);
            $benchmarkTotal = round($benchmarkTotal, 2);
            $difference = round($userTotal - $benchmarkTotal, 2);

            $generalData[] = [
                'year' => (int) $year,
                'user_total' => $userTotal,
                'benchmark_total' => $benchmarkTotal,
                'difference' => $difference,
                'difference_percentage' => $benchmarkTotal > 0
                    ? round($difference / $benchmarkTotal * 100, 2)
                    : null,
                'services' => $serviceDetails,
                'estimated_services' => $estimatedServices,
            ];
        }

        usort($generalData, fn ($a, $b) => $b['year'] <=> $a['year']);

        $options = [];

        if ($generalData !== []) {
            $options[] = ['value' => 'all', 'label' => 'Geral'];
        }

        foreach ($available as $id) {
            $options[] = [
                'value' => (string) $id,
                'label' => $types->get($id)?->name,
            ];
        }

        return [
            'reference_period' => 'annual',
            'service' => 'all',
            'service_options' => $options,
            'data' => $generalData,
            'services' => $serviceData,
        ];
    }
}