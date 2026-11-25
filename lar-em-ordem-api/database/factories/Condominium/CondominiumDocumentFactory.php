<?php

namespace Database\Factories\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Models\File\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Document with a row in files (the file itself doesn't exist in R2: use Storage::fake('r2') in tests).
 *
 * @extends Factory<CondominiumDocument>
 */
class CondominiumDocumentFactory extends Factory
{
    /** category (CondominiumDocumentCategorySeeder) => [title, description] */
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
            // the file belongs to the condominium admin's user (as in an upload)
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