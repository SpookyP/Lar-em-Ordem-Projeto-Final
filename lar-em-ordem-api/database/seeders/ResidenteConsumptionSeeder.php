<?php

namespace Database\Seeders;

use App\Models\Invoice\ConsumptionBenchmark;
use App\Models\Invoice\Invoice;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResidenteConsumptionSeeder extends Seeder
{
    private const EMAIL = 'residente@example.com';
    private const CLEAN_EXISTING = true;
    private const WITH_CREDITS = true;
    private const ELECTRICITY = 1;
    private const WATER = 2;
    private const GAS = 3;

    private const ELECTRICITY_KWH = [
        320, 290, 255, 220, 195, 185,
        205, 210, 200, 230, 270, 310,
    ];

    private const GAS_KWH = [
        420, 380, 300, 200, 120, 80,
        60, 60, 90, 180, 310, 400,
    ];

    private const WATER_M3 = [
        8, 8, 8, 8, 9, 10,
        11, 11, 9, 8, 8, 8,
    ];

    private array $sequence = [];
    private array $annualBenchmarkData = [];

    public function run(): void
    {
        mt_srand(2026);

        $user = User::where('email', self::EMAIL)->firstOrFail();
        $resident = $user->resident;

        if (!$resident) {
            throw new \RuntimeException(
                'O utilizador de teste não tem registo de residente.'
            );
        }

        $contract = PropertyContract::where('resident_id', $resident->id)
            ->orderBy('id')
            ->first();

        if (!$contract) {
            throw new \RuntimeException(
                'O residente não tem nenhum contrato/casa associado.'
            );
        }

        $residentId = (string) $resident->id;
        $propertyId = (string) $contract->property_id;
        $property = Property::with('address')->findOrFail($propertyId);

        Model::unguarded(function () use (
            $residentId,
            $propertyId,
            $property
        ): void {
            DB::transaction(function () use (
                $residentId,
                $propertyId,
                $property
            ): void {
                if (self::CLEAN_EXISTING) {
                    $this->clean($propertyId);
                }

                $this->seedYear($residentId, $propertyId, $property);
            });
        });
    }

    private function seedYear(
        string $residentId,
        string $propertyId,
        Property $property
    ): void {
        $firstMonth = Carbon::now()->startOfMonth()->subMonths(24);

        for ($i = 0; $i < 24; $i++) {
            $start = $firstMonth->copy()->addMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $monthIndex = $start->month - 1;

            $this->seedConsumption(
                $residentId,
                $propertyId,
                $property,
                self::ELECTRICITY,
                'Endesa Energia',
                'ELE',
                $start,
                $end,
                self::ELECTRICITY_KWH[$monthIndex],
                $monthIndex,
                0.08
            );

            $this->seedConsumption(
                $residentId,
                $propertyId,
                $property,
                self::GAS,
                'Galp Gás Natural',
                'GAS',
                $start,
                $end,
                self::GAS_KWH[$monthIndex],
                $monthIndex,
                0.12
            );

            $this->seedConsumption(
                $residentId,
                $propertyId,
                $property,
                self::WATER,
                'EPAL / CM Lisboa',
                'AGU',
                $start,
                $end,
                self::WATER_M3[$monthIndex],
                $monthIndex,
                0.07
            );
        }

        $this->createAnnualBenchmarks($property);
    }

    private function seedConsumption(
        string $residentId,
        string $propertyId,
        Property $property,
        int $typeId,
        string $supplier,
        string $prefix,
        Carbon $start,
        Carbon $end,
        float|int $baseAmount,
        int $monthIndex,
        float $variation = 0.06
    ): void {
        $amount = $this->vary($baseAmount, $variation);

        $items = match ($typeId) {
            self::ELECTRICITY => $this->electricityItems($start, $end, $amount),
            self::GAS => $this->gasItems($start, $end, $amount),
            self::WATER => $this->waterItems($start, $end, $amount),
            default => throw new \InvalidArgumentException(
                "Tipo de consumo inválido: {$typeId}"
            ),
        };

        $invoiceCost = round(array_sum(array_column($items, 'cost')), 2);

        $this->createInvoice(
            $residentId,
            $propertyId,
            $typeId,
            $supplier,
            $prefix,
            $start,
            $end,
            $amount,
            $items
        );

        $costDelta = match ($typeId) {
            self::ELECTRICITY => +5.25,
            self::GAS => -4.80,
            self::WATER => -5.10,
            default => 0.0,
        };

        $amountDelta = match ($typeId) {
            self::ELECTRICITY => +12.0,
            self::GAS => -15.0,
            self::WATER => -1.2,
            default => 0.0,
        };

        $benchmarkCost = max(10.0, $invoiceCost + $costDelta);
        $benchmarkConsumption = max(1.0, $amount + $amountDelta);

        $this->createBenchmark(
            $typeId,
            $property,
            $start,
            $benchmarkConsumption,
            $benchmarkCost
        );
    }

    private function createBenchmark(
        int $typeId,
        Property $property,
        Carbon $start,
        float $consumption,
        float $cost
    ): void {
        ConsumptionBenchmark::updateOrCreate(
            [
                'consumption_type_id' => $typeId,
                'property_type_id' => $property->property_type_id,
                'typology_id' => $property->property_typology_id,
                'region' => $property->address->district,
                'reference_period' => 'monthly',
                'period_start' => $start->toDateString(),
            ],
            [
                'average_value' => round($consumption, 3),
                'average_cost' => round($cost, 2),
                'sample_size' => 64,
            ]
        );

        $this->annualBenchmarkData[$typeId][$start->year][$start->month] = [
            'value' => $consumption,
            'cost' => $cost,
            'days' => $start->daysInMonth,
        ];
    }

    private function createAnnualBenchmarks(Property $property): void
    {
        $minimumMonths = 9;

        foreach ($this->annualBenchmarkData as $typeId => $years) {
            foreach ($years as $year => $months) {
                if (count($months) < $minimumMonths) {
                    continue;
                }

                $coveredDays = array_sum(array_column($months, 'days'));

                if ($coveredDays <= 0) {
                    continue;
                }

                $yearStart = Carbon::create((int) $year, 1, 1)->startOfDay();
                $yearDays = $yearStart->copy()->endOfYear()->dayOfYear;

                $annualConsumption =
                    array_sum(array_column($months, 'value')) * $yearDays / $coveredDays;

                $annualCost =
                    array_sum(array_column($months, 'cost')) * $yearDays / $coveredDays;

                ConsumptionBenchmark::updateOrCreate(
                    [
                        'consumption_type_id' => $typeId,
                        'property_type_id' => $property->property_type_id,
                        'typology_id' => $property->property_typology_id,
                        'region' => $property->address->district,
                        'reference_period' => 'annual',
                        'period_start' => $yearStart->toDateString(),
                    ],
                    [
                        'average_value' => round($annualConsumption, 3),
                        'average_cost' => round($annualCost, 2),
                        'sample_size' => 64,
                    ]
                );
            }
        }
    }

    private function createInvoice(
        string $residentId,
        string $propertyId,
        int $typeId,
        string $supplier,
        string $prefix,
        Carbon $start,
        Carbon $end,
        float $amount,
        array $items
    ): void {
        $cost = round(array_sum(array_column($items, 'cost')), 2);
        $number = ($this->sequence[$typeId] ?? 0) + 1;
        $this->sequence[$typeId] = $number;

        $invoice = Invoice::create([
            'resident_id' => $residentId,
            'property_id' => $propertyId,
            'invoice_number' => sprintf(
                'DEMO-%s/%d/%04d',
                $prefix,
                $end->year,
                $number
            ),
            'issue_date' => $end->copy()->addDays(5)->toDateString(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_amount' => $cost,
            'supplier' => $supplier,
            'file_id' => null,
        ]);

        $consumption = $invoice->consumptions()->create([
            'property_id' => $propertyId,
            'consumption_type_id' => $typeId,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'amount' => round($amount, 3),
            'cost' => $cost,
        ]);

        $consumption->items()->createMany($items);
    }

    private function electricityItems(
        Carbon $start,
        Carbon $end,
        float $kwh
    ): array {
        $days = $this->days($start, $end);
        $energy = round($kwh * 0.1692, 2);
        $fixed = round($days * 0.2478, 2);
        $iec = round($kwh * 0.001, 2);

        $items = [
            $this->item(
                'Energia ativa (Tarifa Simples)',
                'consumption',
                round($kwh, 3),
                'kWh',
                $energy
            ),
            $this->item(
                'Termo de potência contratada',
                'fixed_fee',
                $days,
                'dias',
                $fixed
            ),
            $this->item(
                'Imposto Especial de Consumo (IEC)',
                'tax',
                round($kwh, 3),
                'kWh',
                $iec
            ),
            $this->item(
                'Contribuição Audiovisual',
                'tax',
                1,
                'mês',
                2.85
            ),
            $this->vat('IVA 23%', 0.23, $energy + $fixed + $iec),
        ];

        if (
            self::WITH_CREDITS
            && in_array($start->month, [5, 6, 7, 8, 9], true)
        ) {
            $credit = round($this->vary(11.2, 0.3), 2);

            $items[] = $this->item(
                'Crédito de autoconsumo (injeção na rede)',
                'other',
                round($credit / 0.0831, 3),
                'kWh',
                -$credit
            );
        }

        return $items;
    }

    private function gasItems(
        Carbon $start,
        Carbon $end,
        float $kwh
    ): array {
        $days = $this->days($start, $end);
        $energy = round($kwh * 0.0762, 2);
        $fixed = round($days * 0.1783, 2);

        return [
            $this->item(
                'Gás consumido',
                'consumption',
                round($kwh, 3),
                'kWh',
                $energy
            ),
            $this->item('Termo fixo', 'fixed_fee', $days, 'dias', $fixed),
            $this->item(
                'Taxa de ocupação de subsolo (TOS)',
                'tax',
                1,
                'mês',
                0.80
            ),
            $this->vat('IVA 23%', 0.23, $energy + $fixed),
        ];
    }

    private function waterItems(
        Carbon $start,
        Carbon $end,
        float $m3
    ): array {
        $days = $this->days($start, $end);
        $supply = round($m3 * 0.92, 2);
        $sewage = round($m3 * 0.78, 2);
        $supplyFee = round($days * 0.19, 2);
        $sewageFee = round($days * 0.10, 2);
        $waste = round($days * 0.0745, 2);

        return [
            $this->item(
                'Água consumida',
                'consumption',
                round($m3, 3),
                'm³',
                $supply
            ),
            $this->item(
                'Saneamento (consumo)',
                'consumption',
                round($m3, 3),
                'm³',
                $sewage
            ),
            $this->item(
                'EPAL - Abastecimento de Água (Quota Serviço)',
                'fixed_fee',
                $days,
                'dias',
                $supplyFee
            ),
            $this->item(
                'CMLisboa - Saneamento (Tarifa de Disponibilidade)',
                'fixed_fee',
                $days,
                'dias',
                $sewageFee
            ),
            $this->item(
                'CMLisboa - Resíduos Urbanos (Tarifa de Disponibilidade)',
                'fixed_fee',
                $days,
                'dias',
                $waste
            ),
            $this->vat(
                'IVA 6%',
                0.06,
                $supply + $sewage + $supplyFee + $sewageFee + $waste
            ),
        ];
    }

    private function item(
        string $name,
        string $category,
        float|int|null $amount,
        ?string $unit,
        float $cost
    ): array {
        return [
            'name' => $name,
            'category' => $category,
            'amount' => $amount,
            'unit' => $unit,
            'cost' => round($cost, 2),
        ];
    }

    private function vat(string $name, float $rate, float $base): array
    {
        return $this->item(
            $name,
            'tax',
            round($base, 2),
            'EUR de base',
            round($base * $rate, 2)
        );
    }

    private function days(Carbon $start, Carbon $end): int
    {
        return (int) $start->copy()->startOfDay()
            ->diffInDays($end->copy()->startOfDay()) + 1;
    }

    private function vary(float|int $value, float $pct = 0.06): float
    {
        $variation = (mt_rand(0, 2000) - 1000) / 1000;

        return $value * (1 + ($variation * $pct));
    }

    private function clean(string $propertyId): void
    {
        Invoice::where('property_id', $propertyId)
            ->where('invoice_number', 'like', 'DEMO-%')
            ->delete();
    }
}