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

    protected $fillable = [
        'title', 
        'message', 
        'priority', 
        'expires_at'
        ];

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
            ->withPivot('read_at', 'dismissed_at');
    }

    /** O aviso ainda está em vigor? */
    public function isCurrent(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /** Avisos ainda em vigor (sem data de fim ou com data de fim no futuro). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));
    }

    /** Avisos em vigor que o residente ainda não viu. */
    public function scopeUnreadBy(Builder $query, string $residentId): Builder
    {
        return $query->current()
            ->whereDoesntHave('readers', fn (Builder $q) => $q->where('residents.id', $residentId));
    }

    /** Prioridade alta primeiro, depois os mais recentes. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("case priority when 'high' then 0 when 'normal' then 1 else 2 end")
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}