<?php

namespace App\Models\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    /** @use HasFactory<\Database\Factories\ProposalFactory> */
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
        'availability',
        'status',
        'response_date',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'validity'        => 'date',
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
}
