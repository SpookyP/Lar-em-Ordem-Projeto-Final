<?php

namespace Database\Factories\User;

use App\Models\User\ServiceProvider;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceProvider>
 */
class ServiceProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'company_name' => fake()->company(),
            'nif'          => fake()->numerify('5########'),
            'phone'        => fake()->numerify('9########'),
            'email'        => fake()->unique()->companyEmail(),
            'description'  => fake()->sentence(),
        ];
    }
}
