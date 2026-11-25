<?php

namespace Database\Seeders;

use App\Models\Offer\OfferCategory;
use Illuminate\Database\Seeder;

class OfferCategorySeeder extends Seeder
{
    /**
     * Offer type catalog (provisional: the client hasn't settled the list yet).
     * To change or add types, edit this list.
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