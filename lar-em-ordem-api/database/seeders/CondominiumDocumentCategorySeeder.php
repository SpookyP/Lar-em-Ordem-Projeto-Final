<?php

namespace Database\Seeders;

use App\Models\Condominium\CondominiumDocumentCategory;
use Illuminate\Database\Seeder;

class CondominiumDocumentCategorySeeder extends Seeder
{
    /**
     * Catálogo de categorias dos documentos do condomínio.
     */
    public function run(): void
    {
        $catalog = [
            'Atas de assembleia'                    => 'Atas das assembleias de condóminos.',
            'Regulamento interno'                   => 'Regulamento interno do condomínio.',
            'Orçamento do fundo comum de reserva'   => 'Orçamentos e contas do fundo comum de reserva.',
            'Outros'                                => 'Outros documentos do condomínio.',
        ];

        foreach ($catalog as $name => $description) {
            CondominiumDocumentCategory::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}