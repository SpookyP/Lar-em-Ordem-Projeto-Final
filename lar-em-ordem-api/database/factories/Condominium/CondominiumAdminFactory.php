<?php

namespace Database\Factories\Condominium;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CondominiumAdmin>
 */
class CondominiumAdminFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'contact'    => fake()->phoneNumber(),
            'admin_type' => fake()->randomElement(['professional', 'resident']),
        ];
    }
}