<?php

namespace Database\Factories\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Property\Address;
use Database\Factories\Property\AddressFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Condominium>
 */
class CondominiumFactory extends Factory
{
    private const NAMES = [
        'Edifício Atlântico',
        'Edifício Primavera',
        'Edifício Avenida',
        'Residencial Jardim',
        'Residências do Parque',
        'Torre Norte',
        'Condomínio Quinta Nova',
        'Edifício Miradouro',
    ];

    /** Mesmo alfabeto do CondominiumService (sem 0/O, 1/I/L). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function definition(): array
    {
        [$district, $county] = fake()->randomElement(AddressFactory::LOCATIONS);
        
        return [
            'condominium_admin_id' => CondominiumAdmin::factory(),
            // morada do prédio: sem andar nem fração
            'address_id'           => Address::factory()->state([
                'door'     => null,
                'fraction' => null,
                'county'   => $county,
                'location' => $county,
                'district' => $district,
            ]),
            'name'                 => fake()->randomElement(self::NAMES),
            'reserve_fund'         => fake()->randomFloat(2, 500, 30000),
            'invite_code'          => fake()->unique()->regexify('[' . self::CODE_ALPHABET . ']{8}'),
        ];
    }

    /**
     * Com um código de transferência pendente ($code em claro, guardado como sha256).
     * $days negativo = código já expirado.
     */
    public function withTransferCode(string $code = 'TRANSFER2345', int $days = 7): static
    {
        return $this->state(fn () => [
            'transfer_code_hash'  => hash('sha256', strtoupper($code)),
            'transfer_expires_at' => now()->addDays($days),
        ]);
    }
}