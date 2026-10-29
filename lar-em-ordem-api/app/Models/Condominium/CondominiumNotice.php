<?php

namespace App\Models\Condominium;

use App\Models\User\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondominiumNotice extends Model
{
    /** @use HasFactory<\Database\Factories\Condominium\CondominiumNoticeFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['condominium_id', 'title', 'message', 'priority', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function condominium()
    {
        return $this->belongsTo(Condominium::class);
    }

    /** Residentes que já deram o "visto". */
    public function readers()
    {
        return $this->belongsToMany(Resident::class, 'condominium_notice_reads')
            ->withPivot('read_at');
    }

    /** Avisos ainda em vigor (sem data de fim ou com data de fim no futuro). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));
    }
}