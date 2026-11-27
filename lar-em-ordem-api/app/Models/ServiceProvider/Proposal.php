<?php

namespace App\Models\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProvider\ProposalFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    public function assistanceRequest()
    {
        return $this->belongsTo(AssistanceRequest::class);
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProviderModel::class);
    }

    protected $fillable = [
        'assistance_request_id',
        'service_provider_id',
        'estimated_value',
        'description',
        'validity',
        'available_date',
        'available_from',
        'available_until',
        'status',
        'response_date',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

     protected $casts = [
        'estimated_value' => 'decimal:2',
        'validity'        => 'date',
        'available_date'  => 'date',
        'response_date'   => 'datetime',
    ];

     /**
      * Whether the proposal status is 'pending'.
      *
      * @return bool
      */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Pending but past its validity. Calculated on the fly: the database still says 'pending',
     * so it becomes valid again if the provider extends the validity.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->isPending() && $this->validity !== null && $this->validity->lt(today());
    }

    /**
     * Pending proposals still within their validity.
     *
     * @param Builder $query Query the filter applies to.
     * @return Builder
     */
    public function scopeStillValid(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('validity', '>=', today());
    }

    /**
     * Pending proposals past their validity (the calculated "expired" state).
     *
     * @param Builder $query Query the filter applies to.
     * @return Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('validity', '<', today());
    }

    /**
     * Filters by the status the provider sees: 'pending' only the ones still valid,
     * 'expired' the pending ones past their validity, the others by the stored status.
     *
     * @param Builder $query Query the filter applies to.
     * @param string $status pending/expired/accepted/rejected/withdrawn.
     * @return Builder
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'pending' => $query->stillValid(),
            'expired' => $query->expired(),
            default   => $query->where('status', $status),
        };
    }
}
