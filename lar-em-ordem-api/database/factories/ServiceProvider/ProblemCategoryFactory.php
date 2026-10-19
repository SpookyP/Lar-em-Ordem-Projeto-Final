<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProblemCategory>
 */
class ProblemCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}