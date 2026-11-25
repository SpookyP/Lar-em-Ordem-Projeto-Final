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
     * Offers current today: active, already started and not yet ended.
     * Usage: Offer::current()->get() or $partner->offers()->current()->get()
     *
     * @param Builder $query Query the filters apply to.
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
     * Offers in one status (the partner's offer tabs). The statuses don't overlap:
     * inactive = paused by the partner (whatever the dates); the rest are active offers:
     * scheduled = not started yet, expired = already ended, current = running now.
     *
     * @param Builder $query Query the filter applies to.
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
