<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\ConsumptionBenchmark;
use App\Models\Invoice\ConsumptionType;
use App\Models\Property\Property;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BenchmarkService
{
    /**
     * Compara o consumo da propriedade com o benchmark já calculado (método antigo para listagem geral, se necessário).
     *
     * @return array<string, mixed>
     */
    public function compare(
        Property $property,
        string $period,
        ?int $consumptionTypeId = null,
        ?Carbon $periodStart = null,
    ): array {
        // Carrega as relações necessárias da propriedade
        $property->loadMissing(['address', 'propertyType', 'propertyTypology']);

        $region = $property->address?->district;

        // Constrói a consulta para obter os benchmarks correspondentes à propriedade e região
        $group = ConsumptionBenchmark::query()
            ->where('property_type_id', $property->property_type_id)
            ->where('typology_id', $property->property_typology_id)
            ->where('region', $region)
            ->where('reference_period', $period)
            ->when($consumptionTypeId, fn ($q) => $q->where('consumption_type_id', $consumptionTypeId));

        $start = $this->resolvePeriodStart($group, $period, $periodStart);
        $end = $period === 'monthly' ? $start->copy()->addMonth() : $start->copy()->addYear();

        // Obtém os registos de benchmark indexados pelo tipo de consumo
        $benchmarks = (clone $group)
            ->whereDate('period_start', $start->toDateString())
            ->get()
            ->keyBy('consumption_type_id');

        // Calcula o consumo real da casa para o período
        $own = $this->clientConsumption($property, $start, $end, $consumptionTypeId);

        // Obtém os tipos de consumo disponíveis
        $types = ConsumptionType::query()
            ->when($consumptionTypeId, fn ($q) => $q->whereKey($consumptionTypeId))
            ->orderBy('name')
            ->get();

        // Mapeia cada tipo de consumo para a respetiva comparação estruturada
        $comparisons = $types
            ->map(fn (ConsumptionType $type) => $this->buildComparison($type, $own->get($type->id), $benchmarks->get($type->id)))
            ->filter()
            ->values()
            ->all();

        return [
            'reference_period' => $period,
            'period_start'     => $start->toDateString(),
            'property_type'    => $property->propertyType?->type,
            'typology'         => $property->propertyTypology?->typology,
            'region'           => $region,
            'comparisons'      => $comparisons,
        ];
    }


    
    /**
     * Compara o período específico (mensal ou anual) para o endpoint direto.
     */
    public function comparePeriod(Property $property, string $period, int $consumptionTypeId, Carbon $periodStart): array
    {
        // Carrega as relações da propriedade e identifica a região (distrito)
        $property->loadMissing(['address', 'propertyType', 'propertyTypology']);
        $region = $property->address?->district;

        $end = $period === 'monthly' ? $periodStart->copy()->addMonth() : $periodStart->copy()->addYear();

        // Procura o benchmark estatístico exato para os critérios da propriedade
        $benchmark = ConsumptionBenchmark::query()
            ->where('property_type_id', $property->property_type_id)
            ->where('typology_id', $property->property_typology_id)
            ->where('region', $region)
            ->where('reference_period', $period)
            ->where('consumption_type_id', $consumptionTypeId)
            ->whereDate('period_start', $periodStart->toDateString())
            ->first();

        // Obtém o consumo real do cliente e o tipo de consumo associado
        $own = $this->clientConsumption($property, $periodStart, $end, $consumptionTypeId);
        $type = ConsumptionType::findOrFail($consumptionTypeId);

        // null = sem faturas nesse período (não é o mesmo que consumo 0)
        $clientValue = isset($own[$consumptionTypeId]) ? round($own[$consumptionTypeId]['value'], 2) : null;
        
        // Define a média estatística (null se não existir registo de benchmark)
        $average = $benchmark ? round((float) $benchmark->average_value, 2) : null;

        // Calcula a diferença absoluta da média
        $difference = ($clientValue !== null && $average !== null) ? round($clientValue - $average, 2) : null;
        $percentage = ($difference !== null && $average > 0) ? round(($difference / $average) * 100, 2) : null;

        return [
            'reference_period'      => $period,
            'reference_date'        => $period === 'monthly' ? $periodStart->format('Y-m') : $periodStart->format('Y'),
            'client_consumption'    => $clientValue,
            'average_consumption'   => $average,
            'difference'            => $difference,
            'difference_percentage' => $percentage,
            'unit'                  => $type->unit_of_measure,
        ];
    }



    /**
     * Monta a linha de comparação de um tipo de consumo.
     */
    private function buildComparison(ConsumptionType $type, ?array $client, ?ConsumptionBenchmark $benchmark): ?array
    {
        // Se não existir nem consumo do cliente nem benchmark para este tipo, ignora
        if ($client === null && $benchmark === null) {
            return null;
        }

        $clientValue = $client ? round($client['value'], 3) : null;
        $average = $benchmark ? (float) $benchmark->average_value : null;

        // Calcula a diferença numérica entre o consumo do cliente e a média
        $difference = ($clientValue !== null && $average !== null)
            ? round($clientValue - $average, 3)
            : null;

        // Calcula a percentagem de desvio da média
        $percentage = ($difference !== null && $average > 0)
            ? round($difference / $average * 100, 2)
            : null;

        return [
            'consumption_type_id'   => $type->id,
            'consumption_type'      => $type->name,
            'unit'                  => $type->unit_of_measure,
            'client_consumption'    => $clientValue,
            'average_consumption'   => $average,
            'difference'            => $difference,
            'difference_percentage' => $percentage,
            'sample_size'           => $benchmark?->sample_size,
            'client_coverage'       => $client ? round($client['coverage'], 2) : null,
        ];
    }



    /**
     * Resolve o período de início caso não seja enviado na requisição.
     * procurando o registo mais recente ou usando o mês/ano anterior por defeito.
     */
    private function resolvePeriodStart(Builder $group, string $period, ?Carbon $requested): Carbon
    {
        // Se foi enviado um período específico, normaliza para o início do mês ou do ano
        if ($requested) {
            return $period === 'monthly'
                ? $requested->copy()->startOfMonth()
                : $requested->copy()->startOfYear();
        }

        // Caso contrário, procura o período mais recente disponível na base de dados
        $latest = (clone $group)->max('period_start');

        if ($latest) {
            return Carbon::parse($latest)->startOfDay();
        }

        // Se não houver dados, recua para o mês anterior ou ano anterior como fallback
        return $period === 'monthly'
            ? now()->startOfMonth()->subMonth()
            : now()->startOfYear()->subYear();
    }



    /**
     * Calcula o consumo da casa no período aplicando uma distribuição proporcional por dias.
     */
    private function clientConsumption(Property $property, Carbon $start, Carbon $end, ?int $consumptionTypeId): Collection
    {
        $expectedDays = $this->days($start, $end);
        $lastDay = $end->copy()->subDay();

        return Consumption::query()
            ->where('property_id', $property->id)
            ->whereDate('period_end', '>=', $start->toDateString())
            ->whereDate('period_start', '<', $end->toDateString())
            ->when($consumptionTypeId, fn ($q) => $q->where('consumption_type_id', $consumptionTypeId))
            ->get()
            ->groupBy('consumption_type_id')
            ->map(function (Collection $rows) use ($start, $lastDay, $expectedDays) {
                $value = 0.0;
                $covered = 0;

                // Percorre cada registo de consumo/fatura para calcular a sobreposição de dias no período pretendido
                foreach ($rows as $consumption) {
                    $totalDays = $this->days($consumption->period_start, $consumption->period_end) + 1;

                    // Define os limites de interseção entre a fatura e o período que estamos a calcular
                    $from = $consumption->period_start->max($start);
                    $to = $consumption->period_end->min($lastDay);

                    // Ignora se não houver dias válidos de sobreposição
                    if ($to->lt($from) || $totalDays <= 0) {
                        continue;
                    }

                    $overlap = $this->days($from, $to) + 1;

                    // Acumula o valor proporcional ao número de dias sobrepostos
                    $value += (float) $consumption->amount * $overlap / $totalDays;
                    $covered = min($covered + $overlap, $expectedDays);
                }

                // Se nenhum dia do período tiver cobertura de faturas, retorna nulo
                if ($covered === 0) {
                    return null;
                }

                return [
                    // Estima o período completo quando só parte tem faturas (ex.: 20 dias cobertos de 30)
                    'value'    => $value * $expectedDays / $covered,
                    'coverage' => $covered / $expectedDays,
                ];
            })
            ->filter();
    }



    /** Calcula o número de dias entre duas datas. */
    private function days(Carbon $a, Carbon $b): int
    {
        return (int) abs($a->copy()->startOfDay()->diffInDays($b->copy()->startOfDay()));
    }
}