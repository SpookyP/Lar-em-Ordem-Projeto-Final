<?php

namespace Database\Factories\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Models\File\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Documento com um registo em files (o ficheiro em si não existe no R2: nos testes usar Storage::fake('r2')).
 *
 * @extends Factory<CondominiumDocument>
 */
class CondominiumDocumentFactory extends Factory
{
    /** categoria (CondominiumDocumentCategorySeeder) => [título, descrição] */
    private const CATALOG = [
        'Atas de assembleia'                  => ['Ata da assembleia geral ordinária', 'Aprovação das contas do ano anterior e do orçamento.'],
        'Regulamento interno'                 => ['Regulamento interno do condomínio', 'Regras de utilização das partes comuns.'],
        'Orçamento do fundo comum de reserva' => ['Orçamento anual e fundo comum de reserva', 'Previsão de despesas e valor das quotas.'],
        'Outros'                              => ['Apólice do seguro do prédio', 'Seguro multirriscos das partes comuns.'],
    ];

    public function definition(): array
    {
        $category = fake()->randomElement(array_keys(self::CATALOG));
        [$title, $description] = self::CATALOG[$category];

        return [
            'condominium_id'                   => Condominium::factory(),
            'condominium_document_category_id' => fn () => CondominiumDocumentCategory::firstOrCreate(
                ['name' => $category],
                ['description' => $description]
            )->id,
            // o ficheiro pertence ao utilizador do administrador do condomínio (como no upload)
            'file_id'                          => function (array $attributes) use ($title) {
                $userId = Condominium::find($attributes['condominium_id'])->admin->user_id;

                return File::factory()->create([
                    'user_id'       => $userId,
                    'title'         => $title,
                    'original_name' => Str::slug($title) . '.pdf',
                    'path'          => "users/{$userId}/condominium_documents/" . Str::ulid() . '.pdf',
                    'mime_type'     => 'application/pdf',
                    'category'      => 'condominium_documents',
                ])->id;
            },
            'title'                            => $title,
            'description'                      => $description,
            'document_date'                    => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}