<?php

namespace App\Services\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumDocument;
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
    public function __construct(private CondominiumDocumentService $documents) {}

    /** Characters for the codes (invite and transfer): no 0/O or 1/I/L */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Days the transfer code stays valid. */
    private const TRANSFER_DAYS = 7;

    /** Texts that replace a deleted condominium's data. */
    private const REMOVED_NAME = 'Condomínio removido';
    private const REMOVED_NOTICE = 'Aviso removido';
    private const REMOVED_STREET = 'Morada removida';

    /**
     * Returns the condominiums the user manages, paginated.
     *
     * @param User $user Authenticated user.
     * @param int $perPage Condominiums per page.
     * @return LengthAwarePaginator
     */
    public function listFor(User $user, int $perPage = 5): LengthAwarePaginator
    {
        return Condominium::managedBy($user)
            ->with(['address', 'admin.user'])
            ->withCount('properties')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Creates a condominium (and the building's address) managed by the given admin.
     * The admin, address and invite code are set here and never come from the request.
     *
     * @param array $data Validated data (name, reserve_fund, address).
     * @param CondominiumAdmin $admin Admin creating the condominium.
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
     * Updates the condominium and the building's address (when sent), and generates a new
     * invite code when asked.
     *
     * @param Condominium $condominium Condominium to update.
     * @param array $data Validated data.
     * @return Condominium
     */
    public function update(Condominium $condominium, array $data): Condominium
    {
        DB::transaction(function () use ($condominium, $data) {
            if (isset($data['address'])) {
                $condominium->address->update($data['address']);
            }

            // the old code stops working (properties that already joined are not affected)
            if (! empty($data['regenerate_invite_code'])) {
                $condominium->invite_code = $this->newInviteCode();
            }

            $condominium->fill(Arr::except($data, ['address', 'regenerate_invite_code']))->save();
        });

        return $this->loadFull($condominium, withProperties: true);
    }

  /**
   * Deletes the condominium: unlinks the properties, deletes the documents
   * and anonymises what remains (name, address and notices) before the soft delete.
   *
   * @param Condominium $condominium Condominium to delete.
   * @return void
   */
    public function delete(Condominium $condominium): void
    {
        DB::transaction(function () use ($condominium) {
            Property::where('condominium_id', $condominium->id)->update(['condominium_id' => null]);

            $condominium->documents()->with('file')->get()
                ->each(fn (CondominiumDocument $document) => $this->documents->delete($document));

            $condominium->notices()->update([
                'title'   => self::REMOVED_NOTICE,
                'message' => '',
            ]);

            // district, county and locality don't identify the building; the street and postal code go
            $condominium->address->forceFill([
                'street'      => self::REMOVED_STREET,
                'postal_code' => '0000-000',
                'door'        => null,
                'fraction'    => null,
            ])->save();
            $condominium->address->delete();

            $condominium->forceFill([
                'name'                => self::REMOVED_NAME,
                'transfer_code_hash'  => null,
                'transfer_expires_at' => null,
            ])->save();

            $condominium->delete();
        });
    }

    /**
     * Links one of the resident's properties to the condominium of the invite code.
     * The property needs an active contract of the user and can't already belong to a condominium.
     *
     * @param User $user Authenticated resident.
     * @param string $inviteCode Invite code.
     * @param string $propertyId Property to link.
     * @return Condominium The condominium the property was linked to.
     */
    public function join(User $user, string $inviteCode, string $propertyId): Condominium
    {
        $property = Property::findOrFail($propertyId);

        // reuses PropertyPolicy::view → active contract
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
     * Unlinks a property from the condominium.
     * The admin can remove any property of their condominium; a resident only their own.
     *
     * @param Condominium $condominium Condominium.
     * @param User $user Authenticated user (admin or resident).
     * @param string $propertyId Property to unlink.
     * @return void
     */
    public function leave(Condominium $condominium, User $user, string $propertyId): void
    {
        $property = Property::where('condominium_id', $condominium->id)->find($propertyId);

        $allowed = $property && (
            Gate::forUser($user)->allows('update', $condominium)   // admin
            || Gate::forUser($user)->allows('view', $property)     // resident with an active contract
        );

        if (! $allowed) {
            throw ValidationException::withMessages([
                'property_id' => 'Esta casa não pertence a este condomínio ou não é sua.',
            ]);
        }

        $property->update(['condominium_id' => null]);
    }

    /**
     * Generates a transfer code (12 characters, valid for TRANSFER_DAYS days).
     * Only the hash is stored; the code is returned once. Replaces any previous code.
     *
     * @param Condominium $condominium Condominium to transfer.
     * @return string The code, for the admin to hand to the new one.
     */
    public function startTransfer(Condominium $condominium): string
    {
        do {
            $code = $this->randomCode(12);
            // SHA-256 of the code (one-way hash, 64 hex characters);
            $hash = hash('sha256', $code);
        } while (Condominium::withTrashed()->where('transfer_code_hash', $hash)->exists());

        $condominium->forceFill([
            'transfer_code_hash'  => $hash,
            'transfer_expires_at' => now()->addDays(self::TRANSFER_DAYS),
        ])->save();

        return $code;
    }

    /**
     * Cancels the pending transfer (the code stops working).
     *
     * @param Condominium $condominium Condominium.
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
     * The new admin accepts the transfer with the code they received.
     *
     * @param string $transferCode Transfer code.
     * @param CondominiumAdmin $newAdmin Admin profile of whoever accepts.
     * @return Condominium The condominium, now with the new admin.
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
     * Loads what the resource shows: address, admin, number of properties and,
     * for the admin, the linked fractions (so they can remove a property).
     *
     * @param Condominium $condominium Condominium.
     * @param bool $withProperties Load the properties (only for whoever manages it).
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
     * Hands the condominium to another admin: the document files move to the new admin's
     * user (otherwise they would disappear if the old one deleted their account) and the pending
     * transfer is cleared.
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
     * Generates a unique 8-character invite code.
     */
    private function newInviteCode(): string
    {
        do {
            $code = $this->randomCode(8);
        } while (Condominium::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * Draws a code from CODE_ALPHABET characters (random_int is a secure generator).
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