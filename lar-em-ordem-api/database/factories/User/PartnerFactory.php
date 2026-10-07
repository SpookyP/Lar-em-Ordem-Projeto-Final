<?php

namespace Database\Factories\User;

use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'name'        => fake()->name(),
            'nif'         => fake()->numerify('2########'),
            'phone'       => fake()->numerify('9########'),
            'website'     => fake()->optional()->url(),
            'description' => fake()->sentence(),
        ];
    }
}
