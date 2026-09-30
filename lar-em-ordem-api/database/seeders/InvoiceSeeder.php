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
        // Valores temporarios para testar as Invoice
        $userId = DB::table('users')->insertGetId([
            'name' => 'Utilizador Temporario',
            'email' => 'temp@teste.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => 1,     
            'property_typology_id' => 1,  
            'address_id' => 1,   
            'area' => 100,               
            'fraction' => 'A',             
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Invoice::factory()->count(25)->create([
            'user_id' => $userId,
            'property_id' => $propertyId,
        ]);
    }
}
