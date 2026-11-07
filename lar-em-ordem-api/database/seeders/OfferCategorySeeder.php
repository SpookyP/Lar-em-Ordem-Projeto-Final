<?php

namespace Database\Seeders;

use App\Models\Offer\OfferCategory;
use Illuminate\Database\Seeder;

class OfferCategorySeeder extends Seeder
{
    /**
     * Catálogo de tipos de oferta (base provisória: o cliente ainda não fechou a lista).
     * Para mudar ou acrescentar, basta alterar esta lista.
     */
    public function run(): void
    {
        $catalog = [
            'Seguros',
            'Energia',
            'Telecomunicações',
            'Manutenção e reparações',
            'Limpeza',
            'Mudanças e armazenamento',
            'Segurança',
            'Mobiliário e decoração',
            'Eletrodomésticos e equipamentos',
            'Outros',
        ];

        foreach ($catalog as $name) {
            OfferCategory::firstOrCreate(['name' => $name]);
        }
    }
}