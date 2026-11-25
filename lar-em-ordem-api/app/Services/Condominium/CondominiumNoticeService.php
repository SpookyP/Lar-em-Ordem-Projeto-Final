<?php

namespace App\Services\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use App\Models\User\User;
use App\Notifications\Condominium\CondominiumNoticePublished;
use App\Events\Condominium\CondominiumNoticeBroadcast;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CondominiumNoticeService
{
    /**
     * Condominium notice history (expired ones included), with how many residents have seen each one.
     *
     * @param Condominium $condominium Condominium.
     * @param int $perPage Notices per page.
     * @return LengthAwarePaginator
     */
    public function listForAdmin(Condominium $condominium, int $perPage = 5): LengthAwarePaginator
    {
        return $condominium->notices()
            ->withCount('readers')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Loads the current notices onto the condominium (resident page and admin show).
     * The admin also gets how many residents have seen each notice.
     *
     * @param Condominium $condominium Condominium.
     * @param bool $canManage Whether the requester manages the condominium.
     * @return Condominium
     */
    public function loadCurrent(Condominium $condominium, bool $canManage): Condominium
    {
        return $condominium->load(['notices' => fn ($query) => $query
            ->current()
            ->ordered()
            ->when($canManage, fn ($q) => $q->withCount('readers')),
        ]);
    }

    /**
     * Publishes a notice and emails it (once) to the residents with a property in the condominium.
     *
     * @param Condominium $condominium Condominium.
     * @param array $data Validated data.
     * @return CondominiumNotice
     */
    public function create(Condominium $condominium, array $data): CondominiumNotice
    {
        $notice = new CondominiumNotice($data);
        $notice->forceFill([
            'condominium_id' => $condominium->id,
            'published_at'   => now(),
        ])->save();

        $this->notifyResidents($notice);

        return $notice->loadCount('readers');
    }

    /**
     * Edits a notice. If anything changed and the notice is still current, it becomes
     * unread again for everyone and the email is sent again (as an update).
     *
     * @param CondominiumNotice $notice Notice.
     * @param array $data Validated data.
     * @return CondominiumNotice
     */
    public function update(CondominiumNotice $notice, array $data): CondominiumNotice
    {
        $changed = DB::transaction(function () use ($notice, $data) {
            $notice->update($data);

            if (! $notice->wasChanged()) {
                return false;
            }

            $notice->readers()->detach(); // shows up as unread again

            return true;
        });

        if ($changed && $notice->isCurrent()) {
            $this->notifyResidents($notice, updated: true);
        }

        return $notice->loadCount('readers');
    }

    /**
     * Deletes a notice (its reads go with it by cascade).
     *
     * @param CondominiumNotice $notice Notice.
     * @return void
     */
    public function delete(CondominiumNotice $notice): void
    {
        $notice->delete();
    }

    /**
     * Marks the notice as seen by the resident. Repeating it keeps the date of the first time.
     *
     * @param CondominiumNotice $notice Notice.
     * @param User $user Authenticated resident.
     * @return CondominiumNotice
     */
    public function markRead(CondominiumNotice $notice, User $user): CondominiumNotice
    {
        $residentId = $user->resident->id;

        if (! $notice->readers()->where('residents.id', $residentId)->exists()) {
            $notice->readers()->attach($residentId, ['read_at' => now()]);
        }

        return $notice->setAttribute('read', true);
    }

    /**
     * Notices for the resident's bell: current and not dismissed by them, with "read".
     * Most recent first.
     *
     * @param Condominium $condominium Condominium of the active property.
     * @param User $user Authenticated resident.
     * @return Collection
     */
    public function listForBell(Condominium $condominium, User $user): Collection
    {
        $residentId = $user->resident->id;

        return $condominium->notices()
            ->current()
            ->whereDoesntHave('readers', fn (Builder $r) => $r
                ->where('residents.id', $residentId)
                ->whereNotNull('condominium_notice_reads.dismissed_at'))
            ->withExists(['readers as read' => fn (Builder $r) => $r->where('residents.id', $residentId)])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Mark all as read in the bell: every current notice the resident hasn't seen yet.
     *
     * @param Condominium $condominium Condominium of the active property.
     * @param User $user Authenticated resident.
     * @return int How many notices were marked.
     */
    public function markAllRead(Condominium $condominium, User $user): int
    {
        $residentId = $user->resident->id;
        $now = now();

        $rows = $condominium->notices()
            ->unreadBy($residentId)
            ->pluck('id')
            ->map(fn ($noticeId) => [
                'condominium_notice_id' => $noticeId,
                'resident_id'           => $residentId,
                'read_at'               => $now,
            ])
            ->all();

        DB::table('condominium_notice_reads')->insert($rows);

        return count($rows);
    }

    /**
     * The resident dismisses the notice from their bell (it is also marked as read).
     * The notice itself stays, for the other residents and on the condominium page.
     *
     * @param CondominiumNotice $notice Notice.
     * @param User $user Authenticated resident.
     * @return void
     */
    public function dismiss(CondominiumNotice $notice, User $user): void
    {
        $residentId = $user->resident->id;
        $now = now();

        if ($notice->readers()->where('residents.id', $residentId)->exists()) {
            $notice->readers()->updateExistingPivot($residentId, ['dismissed_at' => $now]);
        } else {
            $notice->readers()->attach($residentId, ['read_at' => $now, 'dismissed_at' => $now]);
        }
    }

    /**
     * Users with an active contract on a property in the condominium (the email recipients).
     */
    private function recipients(Condominium $condominium)
    {
        return User::whereHas('resident.contracts', fn (Builder $q) => $q
            ->active()
            ->whereHas('property', fn (Builder $p) => $p->where('condominium_id', $condominium->id)))
            ->get();
    }

    /**
     * Notifies the condominium's residents: email plus real time (one channel per property).
     */
    private function notifyResidents(CondominiumNotice $notice, bool $updated = false): void
    {
        Notification::send(
            $this->recipients($notice->condominium),
            new CondominiumNoticePublished($notice, $updated)
        );

        broadcast(new CondominiumNoticeBroadcast($notice, $updated));
    }
}