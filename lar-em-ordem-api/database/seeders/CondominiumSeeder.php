<?php

namespace Database\Seeders;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumNotice;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class CondominiumSeeder extends Seeder
{
    /**
     * Condomínio de demonstração (módulo 8):
     * - condominio@example.com administra o "Edifício Brito Capelo" (Matosinhos), código de convite CAPELA23;
     * - a casa do morador@example.com em Matosinhos pertence ao prédio;
     * - três avisos: um alto e um normal em vigor (o normal já lido pelo morador) e um expirado (só no histórico).
     * Sem documentos: precisariam de ficheiros reais no R2.
     * Precisa do AssistanceRequestSeeder (casa de Matosinhos do morador).
     */
    public function run(): void
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        $casa = Property::whereHas('address', fn ($q) => $q->where('county', 'Matosinhos'))
            ->whereHas('contracts', fn ($q) => $q->where('resident_id', $morador->resident->id)->where('is_active', true))
            ->firstOrFail();

        $user = User::factory()->create([
            'name'  => 'administrador',
            'email' => 'condominio@example.com',
        ]);
        $admin = CondominiumAdmin::factory()->professional()->create([
            'user_id' => $user->id,
            'contact' => '223456789',
        ]);
        $user->assignRole('condominium_admin');

        $condominio = Condominium::factory()->create([
            'condominium_admin_id' => $admin->id,
            'address_id'           => Address::create([
                'street'      => 'Rua Brito Capelo',
                'postal_code' => '4450-073',
                'county'      => 'Matosinhos',
                'location'    => 'Matosinhos',
                'district'    => 'Porto',
            ])->id,
            'name'                 => 'Edifício Brito Capelo',
            'reserve_fund'         => 12500,
            'invite_code'          => 'CAPELA23',
        ]);

        $casa->forceFill(['condominium_id' => $condominio->id])->save();

        $base = ['condominium_id' => $condominio->id];

        CondominiumNotice::factory()->create($base + [
            'title'        => 'Corte de água',
            'message'      => 'Por obras na rede pública, a água vai estar cortada na terça-feira, das 9h às 13h.',
            'priority'     => 'high',
            'published_at' => now()->subDay(),
            'expires_at'   => now()->addDays(5),
        ]);

        $lido = CondominiumNotice::factory()->create($base + [
            'title'        => 'Limpeza das escadas',
            'message'      => 'A limpeza das escadas passa a ser feita às quintas-feiras de manhã.',
            'priority'     => 'normal',
            'published_at' => now()->subDays(3),
            'expires_at'   => null,
        ]);
        $lido->readers()->attach($morador->resident->id, ['read_at' => now()->subDays(2)]);

        CondominiumNotice::factory()->expired()->create($base + [
            'title'    => 'Manutenção do elevador',
            'message'  => 'O elevador esteve parado para manutenção na sexta-feira de manhã.',
            'priority' => 'normal',
        ]);
    }
}