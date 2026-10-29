<?php

namespace App\Services\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CondominiumService
{
    /** Sem 0/O nem 1/I/L */
    private const INVITE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Devolve os condomínios que o utilizador administra ou onde tem uma casa, paginados.
     *
     * @param User $user Utilizador autenticado.
     * @param int $perPage Número de condomínios por página.
     * @return LengthAwarePaginator
     */
    public function listFor(User $user, int $perPage = 5): LengthAwarePaginator
    {
        return Condominium::visibleTo($user)
            ->with(['address', 'admin.user'])
            ->withCount('properties')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cria um condomínio (e a morada do prédio) gerido pelo administrador indicado.
     * Administrador, morada e código de convite são definidos aqui, nunca vêm do pedido.
     *
     * @param array $data Dados validados (name, reserve_fund, address).
     * @param CondominiumAdmin $admin Administrador que cria o condomínio.
     * @return Condominium
     */
    public function create(array $data, CondominiumAdmin $admin): Condominium
    {
        $condominium = DB::transaction(function () use ($data, $admin) {
            $address = Address::create($data['address']);

            $condominium = new Condominium(Arr::except($data, 'address'));
            $condominium->forceFill([
                'condominium_admin_id' => $admin->id,
                'address_id'           => $address->id,
                'invite_code'          => $this->newInviteCode(),
            ])->save();

            return $condominium;
        });

        return $this->loadFull($condominium, withProperties: true);
    }

    /**
     * Atualiza o condomínio, a morada do prédio (se vier) e, se pedido, gera um novo código de convite.
     *
     * @param Condominium $condominium Condomínio a atualizar.
     * @param array $data Dados validados.
     * @return Condominium
     */
    public function update(Condominium $condominium, array $data): Condominium
    {
        DB::transaction(function () use ($condominium, $data) {
            if (isset($data['address'])) {
                $condominium->address->update($data['address']);
            }

            // o código antigo deixa de funcionar (quem já entrou não é afetado)
            if (! empty($data['regenerate_invite_code'])) {
                $condominium->invite_code = $this->newInviteCode();
            }

            $condominium->fill(Arr::except($data, ['address', 'regenerate_invite_code']))->save();
        });

        return $this->loadFull($condominium, withProperties: true);
    }

    /**
     * Remove o condomínio (soft delete) e desliga as casas que lhe pertenciam.
     *
     * @param Condominium $condominium Condomínio a remover.
     * @return void
     */
    public function delete(Condominium $condominium): void
    {
        DB::transaction(function () use ($condominium) {
            Property::where('condominium_id', $condominium->id)->update(['condominium_id' => null]);
            $condominium->delete();
        });
    }

    /**
     * Liga uma casa do residente ao condomínio do código de convite.
     * A casa tem de ter contrato ativo do utilizador e não pode pertencer já a um condomínio.
     *
     * @param User $user Residente autenticado.
     * @param string $inviteCode Código de convite.
     * @param string $propertyId Casa a ligar.
     * @return Condominium O condomínio a que a casa ficou ligada.
     */
    public function join(User $user, string $inviteCode, string $propertyId): Condominium
    {
        $property = Property::findOrFail($propertyId);

        // reutiliza PropertyPolicy::view → contrato ativo
        if (Gate::forUser($user)->denies('view', $property)) {
            throw ValidationException::withMessages([
                'property_id' => 'Só pode ligar ao condomínio casas onde tem um contrato ativo.',
            ]);
        }

        $condominium = Condominium::where('invite_code', strtoupper($inviteCode))->first()
            ?? throw ValidationException::withMessages([
                'invite_code' => 'Código de convite inválido.',
            ]);

        if ($property->condominium_id !== null) {
            throw ValidationException::withMessages([
                'property_id' => $property->condominium_id === $condominium->id
                    ? 'Esta casa já pertence a este condomínio.'
                    : 'Esta casa já pertence a outro condomínio. Saia primeiro desse condomínio.',
            ]);
        }

        $property->update(['condominium_id' => $condominium->id]);

        return $this->loadFull($condominium);
    }

    /**
     * Desliga uma casa do condomínio.
     * O administrador pode retirar qualquer casa do seu condomínio; o residente só a sua.
     *
     * @param Condominium $condominium Condomínio.
     * @param User $user Utilizador autenticado (administrador ou residente).
     * @param string $propertyId Casa a desligar.
     * @return void
     */
    public function leave(Condominium $condominium, User $user, string $propertyId): void
    {
        $property = Property::where('condominium_id', $condominium->id)->find($propertyId);

        $allowed = $property && (
            Gate::forUser($user)->allows('update', $condominium)   // administrador (ou SU)
            || Gate::forUser($user)->allows('view', $property)     // residente com contrato ativo
        );

        if (! $allowed) {
            throw ValidationException::withMessages([
                'property_id' => 'Esta casa não pertence a este condomínio ou não é sua.',
            ]);
        }

        $property->update(['condominium_id' => null]);
    }

    /**
     * Carrega o que o resource mostra: morada, administrador, nº de casas e,
     * para o administrador, as frações ligadas (para poder retirar uma casa).
     *
     * @param Condominium $condominium Condomínio.
     * @param bool $withProperties Carregar as casas (só para quem gere).
     * @return Condominium
     */
    public function loadFull(Condominium $condominium, bool $withProperties = false): Condominium
    {
        $condominium->load(['address', 'admin.user'])->loadCount('properties');

        if ($withProperties) {
            $condominium->load('properties.address');
        }

        return $condominium;
    }

    /**
     * Gera um código de convite único com 8 caracteres.
     */
    private function newInviteCode(): string
    {
        $last = strlen(self::INVITE_ALPHABET) - 1;

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::INVITE_ALPHABET[random_int(0, $last)];
            }
        } while (Condominium::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }
}