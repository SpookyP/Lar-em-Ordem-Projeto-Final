<?php

namespace Database\Seeders;

use App\Models\Vault\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Certificado Energético', 'description' => 'Certificados ADENE e SCE'],
            ['name' => 'Caderneta Predial', 'description' => 'Cadernetas da Autoridade Tributária'],
            ['name' => 'Contrato de Arrendamento', 'description' => 'Contratos e aditamentos'],
            ['name' => 'Seguro', 'description' => 'Apólices de seguro multirriscos'],
            ['name' => 'Faturas', 'description' => 'Água, Luz, Gás e Telecomunicações'],
        ];

        foreach ($categories as $category) {
            DocumentCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
