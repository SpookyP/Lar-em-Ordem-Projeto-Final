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

    /** Notices of a deleted condominium are no longer reachable through the API. */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->whereHas('condominium');
    }

    public function condominium()
    {
        return $this->belongsTo(Condominium::class);
    }

    /** Residents who have marked the notice as seen. */
    public function readers()
    {
        return $this->belongsToMany(Resident::class, 'condominium_notice_reads')
            ->withPivot('read_at', 'dismissed_at');
    }

    /** Is the notice still current? */
    public function isCurrent(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /** Notices still current (no end date, or an end date in the future). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));
    }

    /** Current notices the resident hasn't seen yet. */
    public function scopeUnreadBy(Builder $query, string $residentId): Builder
    {
        return $query->current()
            ->whereDoesntHave('readers', fn (Builder $q) => $q->where('residents.id', $residentId));
    }

    /** High priority first, then the most recent. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("case priority when 'high' then 0 when 'normal' then 1 else 2 end")
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}