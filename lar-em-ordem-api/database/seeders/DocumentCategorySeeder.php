<?php

namespace Database\Seeders;

use App\Models\Vault\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Escritura', 'description' => 'Contrato de compra e venda ou documento particular autenticado do imóvel.'],
            ['name' => 'Certidão permanente do registo predial', 'description' => 'Histórico e situação jurídica do imóvel (proprietários, hipotecas ou encargos).'],
            ['name' => 'Caderneta predial', 'description' => 'Documento fiscal emitido pelas Finanças com o Valor Patrimonial Tributário (VPT) e áreas.'],
            ['name' => 'Licença de utilização', 'description' => 'Autorização camarária que atesta a conformidade e habitabilidade do imóvel.'],
            ['name' => 'Certificado energético', 'description' => 'Avaliação de desempenho energético do imóvel. Validade habitual de 10 anos (ou 6 anos em certos casos).'],
            ['name' => 'Comprovativos de IMT', 'description' => 'Comprovativo de liquidação do Imposto Municipal sobre as Transmissões Onerosas de Imóveis.'],
            ['name' => 'Imposto do Selo', 'description' => 'Comprovativo de pagamento do Imposto do Selo associado à aquisição ou crédito habitação.'],
            ['name' => 'Comprovativos de IMI', 'description' => 'Imposto Municipal sobre Imóveis. Prazo anual (pagamento em abril, julho e novembro, ou prestação única).'],
            ['name' => 'Comprovativo de AIMI', 'description' => 'Adicional ao IMI (se aplicável). Apenas devido quando o património imobiliário total ultrapassa 600.000 € por pessoa (ou 1.200.000 € para casais com tributação conjunta).'],
            ['name' => 'Contrato e mapa de amortizações', 'description' => 'Condições do crédito habitação, FINE e plano financeiro de prestações.'],
            ['name' => 'Declaração anual de juros', 'description' => 'Declaração emitida pelo banco com os encargos anuais do crédito habitação.'],
            ['name' => 'Multirriscos habitação e seguro de vida', 'description' => 'Apólices e recibos dos seguros obrigatórios do imóvel/crédito. Validade de renovação anual ou conforme a apólice.'],
            ['name' => 'Contrato de arrendamento', 'description' => 'Contrato de locação do imóvel e respetivas renovações (sujeito a prazo de validade).'],
            ['name' => 'Faturas', 'description' => 'Água, Luz, Gás e Telecomunicações'],
            ['name' => 'Outros', 'description' => 'Outros documentos diversos associados ao imóvel ou habitação.'],
        ];

        foreach ($categories as $category) {
            DocumentCategory::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
