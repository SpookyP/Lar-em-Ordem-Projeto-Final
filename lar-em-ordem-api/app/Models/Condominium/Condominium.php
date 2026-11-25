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

    /** Is the user this condominium's admin? */
    public function isManagedBy(?User $user): bool
    {
        $adminId = $user?->condominium_admin?->id;

        return $adminId !== null && $this->condominium_admin_id === $adminId;
    }

    /** Does the user have an active contract on a property in this condominium? */
    public function hasMember(?User $user): bool
    {
        $residentId = $user?->resident?->id;

        return $residentId !== null && $this->properties()
            ->whereHas('contracts', fn (Builder $q) => $q
                ->where('resident_id', $residentId)
                ->active())
            ->exists();
    }

    /** Condominiums the user manages. */
    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        $adminId = $user->condominium_admin?->id;

        // no admin profile: none (a where with null would turn into "is null")
        return $adminId
            ? $query->where('condominium_admin_id', $adminId)
            : $query->whereRaw('0 = 1');
    }
}