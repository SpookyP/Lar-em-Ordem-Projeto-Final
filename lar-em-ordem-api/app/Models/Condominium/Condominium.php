<?php

namespace App\Models\Condominium;

use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Condominium extends Model
{
    /** @use HasFactory<\Database\Factories\Condominium\CondominiumFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    protected $table = 'condominia';

    protected $fillable = [
        'condominium_admin_id',
        'address_id',
        'name',
        'reserve_fund',
    ];

    protected $hidden = ['invite_code', 'transfer_code_hash'];

    protected function casts(): array
    {
        return [
            'reserve_fund'        => 'decimal:2',
            'transfer_expires_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(CondominiumAdmin::class, 'condominium_admin_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function notices()
    {
        return $this->hasMany(CondominiumNotice::class);
    }

    public function documents()
    {
        return $this->hasMany(CondominiumDocument::class);
    }

    /** O utilizador é o administrador deste condomínio? */
    public function isManagedBy(?User $user): bool
    {
        $adminId = $user?->condominium_admin?->id;

        return $adminId !== null && $this->condominium_admin_id === $adminId;
    }

    /** O utilizador tem contrato ativo numa casa deste condomínio? */
    public function hasMember(?User $user): bool
    {
        $residentId = $user?->resident?->id;

        return $residentId !== null && $this->properties()
            ->whereHas('contracts', fn (Builder $q) => $q
                ->where('resident_id', $residentId)
                ->where('is_active', true))
            ->exists();
    }

    /** Condomínios que o utilizador administra ou onde tem uma casa. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $adminId = $user->condominium_admin?->id;
        $residentId = $user->resident?->id;

        // sem perfil de admin nem de residente: lista vazia (um where vazio devolvia tudo)
        if (! $adminId && ! $residentId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(fn (Builder $q) => $q
            ->when($adminId, fn (Builder $q) => $q->orWhere('condominium_admin_id', $adminId))
            ->when($residentId, fn (Builder $q) => $q->orWhereHas('properties.contracts', fn (Builder $c) => $c
                ->where('resident_id', $residentId)
                ->where('is_active', true))));
    }
}