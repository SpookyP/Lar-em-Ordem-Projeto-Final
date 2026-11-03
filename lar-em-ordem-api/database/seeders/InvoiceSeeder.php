<?php

namespace Database\Seeders;

use App\Models\User\Resident;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    private const SUPPLIERS = ['EDP Comercial', 'Galp', 'Iberdrola', 'EPAL', 'Águas do Porto', 'MEO', 'NOS'];

    private int $sequence = 0;

    public function run(): void
    {
        // firstOrCreate keeps the seeder repeatable (the 'hashed' cast takes care of the password)
        $user = User::firstOrCreate(
            ['email' => 'temp@teste.com'],
            ['name' => 'Utilizador Temporario', 'password' => 'password']
        );

        $resident = Resident::firstOrCreate(
            ['user_id' => $user->id],
            ['nif'     => '123456789']
        );

        $propertyIds = DB::table('properties')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        if (empty($propertyIds)) {
            return;
        }

        $thisMonth = now()->startOfMonth();
        $rows = [];

        // Last 3 months: every property has an invoice.
        for ($month = 3; $month >= 1; $month--) {
            $start = $thisMonth->copy()->subMonths($month);

            foreach ($propertyIds as $propertyId) {
                $rows[] = $this->invoiceRow($resident->id, $propertyId, $start);
            }
        }

        // Previous year: ~90% of the properties have an invoice each month
        // (enough coverage to test the annual benchmark).
        $lastYearStart = $thisMonth->copy()->subYear()->startOfYear();

        for ($month = 0; $month < 12; $month++) {
            $start = $lastYearStart->copy()->addMonths($month);

            foreach ($propertyIds as $propertyId) {
                if (rand(1, 100) > 90) {
                    continue;
                }

                $rows[] = $this->invoiceRow($resident->id, $propertyId, $start);
            }
        }

        // plain inserts in chunks: one query per 500 invoices instead of one per invoice
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('invoices')->insert($chunk);
        }
    }

    private function invoiceRow(string $residentId, string $propertyId, Carbon $start): array
    {
        $now = now();

        return [
            'id'             => (string) Str::ulid(),
            'resident_id'    => $residentId,
            'property_id'    => $propertyId,
            'invoice_number' => 'FT-' . str_pad((string) ++$this->sequence, 5, '0', STR_PAD_LEFT),
            'issue_date'     => $start->copy()->addMonth()->addDays(10)->toDateString(),
            'period_start'   => $start->toDateString(),
            'period_end'     => $start->copy()->endOfMonth()->toDateString(),
            'total_amount'   => round(mt_rand(2000, 50000) / 100, 2),
            'supplier'       => self::SUPPLIERS[array_rand(self::SUPPLIERS)],
            'file_id'        => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
    }
}