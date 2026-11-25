<?php

namespace App\Models\ServiceProvider;

use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User\Resident;

class AssistanceRequest extends Model
{
     /** @use HasFactory<\Database\Factories\ServiceProvider\AssistanceRequestFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function problemCategory()
    {
        return $this->belongsTo(ProblemCategory::class);
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Does the request belong to this user? (compared with their resident profile)
     *
     * @param User|null $user Authenticated user.
     * @return bool
     */
    public function isOwnedBy(?User $user): bool
    {
        $residentId = $user?->resident?->id;

        return $residentId !== null && $this->resident_id === $residentId;
    }
    
    /**
     * The accepted proposal of this request (at most one).
     */
    public function acceptedProposal()
    {
        return $this->hasOne(Proposal::class)->where('status', 'accepted');
    }

   protected $fillable = [
        'property_id',
        'resident_id',
        'problem_category_id',
        'title',
        'description',
        'status',
        'closed_at',
    ];

    protected $attributes = [
        'status'=>'open',
    ];

     protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Whether the request status is 'open'.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
         return $this->status === 'open';
    }
    /**
     * Requests a service provider can see and answer:
     * open, not opened by the provider, with no proposal of theirs,
     * in the categories of their specialties and the counties of their zones.
     * No specialties = every category; no zones = every county.
     * Usage: AssistanceRequest::availableFor($provider)->get()
     *
     * @param Builder $query Query the filters apply to.
     * @param ServiceProviderModel $provider Provider to look up requests for.
     * @return Builder
     */
    public function scopeAvailableFor(Builder $query, ServiceProviderModel $provider): Builder
    {
        $categoryIds = $provider->specialties()->pluck('problem_category_id')->unique();

        $counties = $provider->zones()
            ->get(['district', 'county'])
            ->unique(fn ($zone) => $zone->district . '|' . $zone->county);

        return $query->where('status', 'open')
            ->whereHas('resident', fn (Builder $r) => $r->where('user_id', '!=', $provider->user_id))
            ->whereDoesntHave('proposals', fn (Builder $q) => $q->where('service_provider_id', $provider->id))
            // no specialties: every category
            ->when($categoryIds->isNotEmpty(), fn (Builder $q) => $q->whereIn('problem_category_id', $categoryIds))
            // no zones: every county
            ->when($counties->isNotEmpty(), fn (Builder $q) => $q->whereHas('property.address', function (Builder $address) use ($counties) {
                $address->where(function (Builder $any) use ($counties) {
                    foreach ($counties as $zone) {
                        $any->orWhere(fn (Builder $z) => $z
                            ->where('district', $zone->district)
                            ->where('county', $zone->county));
                    }
                });
            }));
    }
}
