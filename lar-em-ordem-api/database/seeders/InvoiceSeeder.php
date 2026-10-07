<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Models\Invoice\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Utilizador temporário para os testes
        $userId = DB::table('users')->insertGetId([
            'name'          =>  'Utilizador Temporario',
            'email'         =>  'temp@teste.com',
            'password'      =>  bcrypt('password'),
            'created_at'    =>  now(),
            'updated_at'    =>  now(),
        ]);

        // Criar 6 propriedades no Porto (Passa no MIN_SAMPLE de 5 para criar o benchmark)
        // Criar 2 propriedades em Braga (Falha no MIN_SAMPLE para testar exclusão correta)
        $propertyIds = [];
        $regions = array_merge(array_fill(0, 6, 'Porto'), array_fill(0, 6, 'Braga'));

        foreach ($regions as $i => $region) {
            $addressId = DB::table('addresses')->insertGetId([
                'street'        =>  'Rua Benchmark ' . ($i + 1),
                'postal_code'   =>  '4000-00' . ($i + 1),
                'door'          =>  (string) ($i + 1),
                'county'        =>  $region,
                'location'      =>  $region,
                'district'      =>  $region,
                'created_at'    =>  now(),
                'updated_at'    =>  now(),
            ]);

            $propertyId = DB::table('properties')->insertGetId([
                'property_type_id'      =>  1,
                'property_typology_id'  =>  1,
                'address_id'            =>  $addressId,
                'condominium_id'        =>  null,
                'area'                  =>  100,
                'fraction'              =>  'A' . ($i + 1),
                'created_at'            =>  now(),
                'updated_at'            =>  now(),
            ]);

            $propertyIds[] = $propertyId;
        }

        /*
         * Preparar os meses a gerar:
         * 12 meses do ano anterior (garante cobertura >90% para o Annual_benchmark.py)
         * 9 meses do ano atual (para testes correntes)
         */
        $datesToGenerate = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $datesToGenerate[] = now()->subYear()->startOfYear()->addMonths($month - 1);
        }
        
        for ($month = 1; $month <= 9; $month++) {
            $datesToGenerate[] = now()->startOfYear()->addMonths($month - 1);
        }

        foreach ($datesToGenerate as $start) {
            foreach ($propertyIds as $propertyId) {
                Invoice::factory()->create([
                    'user_id'       =>  $userId,
                    'property_id'   =>  $propertyId,

                    // Garantir que a invoice pertence a este mês específico
                    'period_start'  =>  $start->toDateString(),
                    'period_end'    =>  $start->copy()->endOfMonth()->toDateString(),
                    'issue_date'    =>  $start->copy()->addMonth()->addDays(10)->toDateString(),
                ]);
            }
        }
    }
}