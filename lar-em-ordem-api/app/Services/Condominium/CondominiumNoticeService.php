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
     * Histórico de avisos do condomínio (inclui expirados), com o nº de residentes que já os viram.
     *
     * @param Condominium $condominium Condomínio.
     * @param int $perPage Número de avisos por página.
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
     * Carrega no condomínio os avisos em vigor (página do residente e show do administrador).
     * O administrador recebe também quantos residentes já viram cada aviso.
     *
     * @param Condominium $condominium Condomínio.
     * @param bool $canManage Se quem pede gere o condomínio.
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
     * Publica um aviso e envia-o por email (uma única vez) aos residentes com casa no condomínio.
     *
     * @param Condominium $condominium Condomínio.
     * @param array $data Dados validados.
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
     * Edita um aviso. Se alguma coisa mudou e o aviso ainda está em vigor, volta a ficar
     * "por ler" para todos e o email é reenviado (como atualização).
     *
     * @param CondominiumNotice $notice Aviso.
     * @param array $data Dados validados.
     * @return CondominiumNotice
     */
    public function update(CondominiumNotice $notice, array $data): CondominiumNotice
    {
        $changed = DB::transaction(function () use ($notice, $data) {
            $notice->update($data);

            if (! $notice->wasChanged()) {
                return false;
            }

            $notice->readers()->detach(); // volta a aparecer como por ler

            return true;
        });

        if ($changed && $notice->isCurrent()) {
            $this->notifyResidents($notice, updated: true);
        }

        return $notice->loadCount('readers');
    }

    /**
     * Apaga um aviso (e os "vistos", por cascade).
     *
     * @param CondominiumNotice $notice Aviso.
     * @return void
     */
    public function delete(CondominiumNotice $notice): void
    {
        $notice->delete();
    }

    /**
     * Marca o aviso como visto pelo residente. Repetir não altera a data do primeiro visto.
     *
     * @param CondominiumNotice $notice Aviso.
     * @param User $user Residente autenticado.
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
     * Avisos para o "sino" do residente: em vigor e não eliminados por ele, com "read".
     * Mais recentes primeiro.
     *
     * @param Condominium $condominium Condomínio da casa ativa.
     * @param User $user Residente autenticado.
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
     * "Marcar tudo como lido no "sino": todos os avisos em vigor que o residente ainda não viu.
     *
     * @param Condominium $condominium Condomínio da casa ativa.
     * @param User $user Residente autenticado.
     * @return int Quantos avisos foram marcados.
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
     * O residente elimina o aviso do seu "sino" (fica também marcado como lido).
     * O aviso não é apagado: continua para os outros e na página do condomínio.
     *
     * @param CondominiumNotice $notice Aviso.
     * @param User $user Residente autenticado.
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
     * Utilizadores com contrato ativo numa casa do condomínio (destinatários do email).
     */
    private function recipients(Condominium $condominium)
    {
        return User::whereHas('resident.contracts', fn (Builder $q) => $q
            ->where('is_active', true)
            ->whereHas('property', fn (Builder $p) => $p->where('condominium_id', $condominium->id)))
            ->get();
    }

    /**
    * Avisa os residentes do condomínio: email + tempo real (canal de cada casa).
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