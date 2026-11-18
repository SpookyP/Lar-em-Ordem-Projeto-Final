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
     * Indica se a proposta tem status 'pending'.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Pendente mas já fora da validade. É calculado: na base de dados continua 'pending',
     * por isso volta a ser válida se o prestador prolongar a validade.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->isPending() && $this->validity !== null && $this->validity->lt(today());
    }

    /**
     * Propostas pendentes ainda dentro da validade.
     *
     * @param Builder $query Query a que se aplica o filtro.
     * @return Builder
     */
    public function scopeStillValid(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('validity', '>=', today());
    }
}
