<?php

namespace App\Services\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\File\File;
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
    /** Caracteres dos códigos (convite e transferência): sem 0/O nem 1/I/L */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Dias de validade do código de transferência. */
    private const TRANSFER_DAYS = 7;

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
     * Atualiza o condomínio, a morada do prédio (se vier), gera novo código de convite (se pedido)
     * e muda de administrador (só o SU envia condominium_admin_id — validado no request).
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

            if (isset($data['condominium_admin_id'])) {
                $this->changeAdmin($condominium, CondominiumAdmin::findOrFail($data['condominium_admin_id']));
            }

            // o código antigo deixa de funcionar (quem já entrou não é afetado)
            if (! empty($data['regenerate_invite_code'])) {
                $condominium->invite_code = $this->newInviteCode();
            }

            $condominium->fill(Arr::except($data, ['address', 'regenerate_invite_code', 'condominium_admin_id']))->save();
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
     * Gera um código de transferência (12 caracteres, válido TRANSFER_DAYS dias).
     * Só o hash fica guardado; o código é devolvido uma única vez. Substitui um código anterior.
     *
     * @param Condominium $condominium Condomínio a transferir.
     * @return string O código, para o administrador entregar ao novo.
     */
    public function startTransfer(Condominium $condominium): string
    {
        do {
            $code = $this->randomCode(12);
            // Calcula o SHA-256 (algoritmo) do código (hash unidirecional de 64 caracteres hex);
            $hash = hash('sha256', $code);
        } while (Condominium::withTrashed()->where('transfer_code_hash', $hash)->exists());

        $condominium->forceFill([
            'transfer_code_hash'  => $hash,
            'transfer_expires_at' => now()->addDays(self::TRANSFER_DAYS),
        ])->save();

        return $code;
    }

    /**
     * Cancela a transferência pendente (o código deixa de funcionar).
     *
     * @param Condominium $condominium Condomínio.
     * @return void
     */
    public function cancelTransfer(Condominium $condominium): void
    {
        $condominium->forceFill([
            'transfer_code_hash'  => null,
            'transfer_expires_at' => null,
        ])->save();
    }

    /**
     * O novo administrador aceita a transferência com o código recebido.
     *
     * @param string $transferCode Código de transferência.
     * @param CondominiumAdmin $newAdmin Perfil de administrador de quem aceita.
     * @return Condominium O condomínio, já com o novo administrador.
     */
    public function claim(string $transferCode, CondominiumAdmin $newAdmin): Condominium
    {
        $condominium = Condominium::where('transfer_code_hash', hash('sha256', strtoupper($transferCode)))
            ->where('transfer_expires_at', '>', now())
            ->first()
            ?? throw ValidationException::withMessages([
                'transfer_code' => 'Código de transferência inválido ou expirado.',
            ]);

        if ($condominium->condominium_admin_id === $newAdmin->id) {
            throw ValidationException::withMessages([
                'transfer_code' => 'Já é o administrador deste condomínio.',
            ]);
        }

        $this->changeAdmin($condominium, $newAdmin);

        return $this->loadFull($condominium, withProperties: true);
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
     * Passa o condomínio para outro administrador: os ficheiros dos documentos passam para
     * o utilizador do novo (senão desapareciam se o antigo apagasse a conta) e a transferência
     * pendente é anulada.
     */
    private function changeAdmin(Condominium $condominium, CondominiumAdmin $newAdmin): void
    {
        DB::transaction(function () use ($condominium, $newAdmin) {
            File::whereIn('id', $condominium->documents()->select('file_id'))
                ->update(['user_id' => $newAdmin->user_id]);

            $condominium->forceFill([
                'condominium_admin_id' => $newAdmin->id,
                'transfer_code_hash'   => null,
                'transfer_expires_at'  => null,
            ])->save();
        });
    }

    /**
     * Gera um código de convite único com 8 caracteres.
     */
    private function newInviteCode(): string
    {
        do {
            $code = $this->randomCode(8);
        } while (Condominium::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * Sorteia um código com caracteres de CODE_ALPHABET (random_int: gerador seguro).
     */
    private function randomCode(int $length): string
    {
        $last = strlen(self::CODE_ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $last)];
        }

        return $code;
    }
}