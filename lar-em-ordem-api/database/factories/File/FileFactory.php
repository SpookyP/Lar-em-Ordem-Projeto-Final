<?php

namespace Database\Factories\File;

use App\Models\File\File;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    private const TITLES = [
        'Contrato de arrendamento', 'Fatura da eletricidade', 'Fatura da água',
        'Seguro multirriscos', 'Certificado energético', 'Caderneta predial',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = Str::uuid();
        $title = $this->faker->randomElement(self::TITLES);

        return [
            'user_id'       => User::factory(),
            'title'         => $title,
            'original_name' => Str::slug($title) . '.pdf',
            'path'          => "users/1/documents/{$uuid}.pdf",
            'mime_type'     => 'application/pdf',
            'size_in_bytes' => $this->faker->numberBetween(10240, 5242880),
            'category'      => $this->faker->randomElement(['contract', 'invoice', 'id_document']),
        ];
    }
}
