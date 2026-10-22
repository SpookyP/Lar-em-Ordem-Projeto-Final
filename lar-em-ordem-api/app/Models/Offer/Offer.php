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

    protected $attributes = [
        'active' => true,
    ];

    protected $fillable = [
        'partner_id',
        'title',
        'description',
        'type',
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
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->whereHas('partner');
    }

}
