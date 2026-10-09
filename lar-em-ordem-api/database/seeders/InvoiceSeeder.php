<?php

namespace Database\Seeders;

use App\Models\Invoice\Invoice;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate torna o seeder repetível (o cast 'hashed' trata da password)
        $user = User::firstOrCreate(
            ['email' => 'temp@teste.com'],
            ['name' => 'Utilizador Temporario', 'password' => 'password']
        );

        $propertyIds = DB::table('properties')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        if (empty($propertyIds)) {
            return;
        }

        $thisMonth = now()->startOfMonth();

        // Últimos 3 meses: todas as propriedades têm fatura.
        for ($month = 3; $month >= 1; $month--) {
            $start = $thisMonth->copy()->subMonths($month);

            foreach ($propertyIds as $propertyId) {
                $this->createInvoice($user->id, $propertyId, $start);
            }
        }

        // Ano anterior: ~90% das propriedades têm fatura em cada mês
        // (cobertura suficiente para testar o benchmark anual).
        $lastYearStart = $thisMonth->copy()->subYear()->startOfYear();

        for ($month = 0; $month < 12; $month++) {
            $start = $lastYearStart->copy()->addMonths($month);

            foreach ($propertyIds as $propertyId) {
                if (rand(1, 100) > 90) {
                    continue;
                }

                $this->createInvoice($user->id, $propertyId, $start);
            }
        }
    }

    private function createInvoice(int $userId, int $propertyId, Carbon $start): void
    {
        Invoice::factory()->create([
            'user_id'      => $userId,
            'property_id'  => $propertyId,
            'period_start' => $start->toDateString(),
            'period_end'   => $start->copy()->endOfMonth()->toDateString(),
            'issue_date'   => $start->copy()->addMonth()->addDays(10)->toDateString(),
        ]);
    }
}