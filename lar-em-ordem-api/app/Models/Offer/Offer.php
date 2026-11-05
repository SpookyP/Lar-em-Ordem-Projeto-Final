<?php

namespace App\Models\Offer;

use App\Models\User\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;



class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\Offer\OfferFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function category()
    {
        return $this->belongsTo(OfferCategory::class, 'offer_category_id');
    }

    protected $attributes = [
        'active' => true,
    ];

    protected $fillable = [
        'partner_id',
        'title',
        'description',
        'offer_category_id',
        'url',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Ofertas em vigor hoje: ativas, já começaram e ainda não terminaram.
     * Uso: Offer::current()->get() ou $partner->offers()->current()->get()
     *
     * @param Builder $query Query a que se aplicam os filtros.
     * @return Builder
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('active', true)
            ->whereDate('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->whereHas('partner');
    }

    /**
     * Ofertas num estado (separadores das ofertas do parceiro). Os estados não se sobrepõem:
     * inactive = pausada pelo parceiro (seja qual for a data); os outros são de ofertas ativas:
     * scheduled = ainda não começou, expired = já terminou, current = em vigor.
     *
     * @param Builder $query Query a que se aplica o filtro.
     * @param string $status current|scheduled|expired|inactive
     * @return Builder
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        $today = now()->toDateString();

        return match ($status) {
            'inactive'  => $query->where('active', false),
            'scheduled' => $query->where('active', true)->whereDate('start_date', '>', $today),
            'expired'   => $query->where('active', true)->whereNotNull('end_date')->whereDate('end_date', '<', $today),
            'current'   => $query->current(),
        };
    }
}
