<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\ServiceSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSpecialty>
 */
class ServiceSpecialtyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'problem_category_id' => ProblemCategory::factory(),
            'name'                => fake()->unique()->words(2, true),
            'description'         => fake()->sentence(),
        ];
    }
}