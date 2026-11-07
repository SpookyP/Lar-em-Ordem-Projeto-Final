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
            'contact'    => fake()->randomElement(['22', '91', '93', '96']) . fake()->numerify('#######'),
            'admin_type' => fake()->randomElement(['professional', 'resident']),
        ];
    }

    /** Empresa de gestão de condomínios. */
    public function professional(): static
    {
        return $this->state(['admin_type' => 'professional']);
    }

    /** Condómino que administra o próprio prédio. */
    public function resident(): static
    {
        return $this->state(['admin_type' => 'resident']);
    }
}
