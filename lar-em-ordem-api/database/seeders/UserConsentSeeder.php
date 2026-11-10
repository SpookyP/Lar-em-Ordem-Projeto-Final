<?php

namespace Database\Seeders;

use App\Models\User\User;
use App\Services\Privacy\ConsentService;
use Illuminate\Database\Seeder;

/**
 * Seeded users accepted the policy and share their data, so the benchmarks have data to work with.
 */
class UserConsentSeeder extends Seeder
{
    public function run(ConsentService $consents): void
    {
        User::query()->each(fn (User $user) => $consents->recordSignUp($user, [
            'privacy_policy' => true,
            'data_sharing' => true,
        ], 'seed'));
    }
}