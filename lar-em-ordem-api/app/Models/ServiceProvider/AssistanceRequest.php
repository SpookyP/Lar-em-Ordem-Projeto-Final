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

class AssistanceRequest extends Model
{
     /** @use HasFactory<\Database\Factories\ServiceProvider\AssistanceRequestFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    public function user()
    {
        return $this->belongsTo(User::class);
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
     * A proposta aceite deste pedido (no máximo uma).
     */
    public function acceptedProposal()
    {
        return $this->hasOne(Proposal::class)->where('status', 'accepted');
    }

   protected $fillable = [
        'property_id',
        'user_id',
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
     * Indica se o pedido tem status 'open'.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
         return $this->status === 'open';
    }
    /**
     * Pedidos que um prestador pode ver e responder:
     * abertos, numa das suas categoria, num concelho
     * onde atua, não  abertos por ele próprio,
     * e ainda não enviou proposta para o pedido
     * Uso: AssistanceRequest::availableFor($provider)->get()
     *
     * @param Builder $query Query a que se aplicam os filtros.
     * @param ServiceProviderModel $provider Prestador para quem se procuram pedidos.
     * @return Builder
     */
    public function scopeAvailableFor(Builder $query, ServiceProviderModel $provider): Builder
    {
        $categoryIds = $provider->specialties()->pluck('problem_category_id')->unique();

        $counties = $provider->zones()
            ->get(['district', 'county'])
            ->unique(fn ($zone) => $zone->district . '|' . $zone->county);

        
        if (! $provider->active || $categoryIds->isEmpty() || $counties->isEmpty()) {
            return $query->whereRaw('1 = 0'); // condição impossível, garantindo que a consulta não devolve resultados
        }

        return $query->where('status', 'open')
            ->where('user_id', '!=', $provider->user_id)
            ->whereIn('problem_category_id', $categoryIds)
            ->whereDoesntHave('proposals', fn (Builder $q) => $q->where('service_provider_id', $provider->id))
            ->whereHas('property.address', function (Builder $address) use ($counties) {
                $address->where(function (Builder $any) use ($counties) {
                    foreach ($counties as $zone) {
                        $any->orWhere(fn (Builder $q) => $q
                            ->where('district', $zone->district)
                            ->where('county', $zone->county));
                    }
                });
            });
    }

}
