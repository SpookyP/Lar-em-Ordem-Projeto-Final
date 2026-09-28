<?php

namespace Database\Factories\Vault;

use App\Models\Vault\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentCategory>
 */
class DocumentCategoryFactory extends Factory
{
    /**
     * O Model associado a esta Factory.
     */
    protected $model = DocumentCategory::class;

    /**
     * Define o estado padrão do modelo.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
