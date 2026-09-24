<?php

namespace App\Services\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use Illuminate\Database\Eloquent\Collection;

class OfferService
{
    /**
     * Cria uma nova oferta, associando-a automaticamente ao ID do parceiro.
     *
     * @param array $data Dados validados para a criação da oferta.
     * @param Partner $partner Instância do parceiro que está a criar a oferta.
     * @return Offer
     */
    public function create(array $data, Partner $partner): Offer
    {
        return Offer::create($data + [
            'partner_id' => $partner->id,
        ]);
    }

    /**
     * Atualiza os dados de uma oferta existente.
     *
     * @param Offer $offer Instância da oferta a ser atualizada.
     * @param array $data Dados validados para a atualização.
     * @return Offer
     */
    public function update(Offer $offer, array $data): Offer
    {
        $offer->update($data);
        return $offer;
    }

    /**
     * Elimina permanentemente uma oferta da base de dados.
     *
     * @param Offer $offer Instância da oferta a ser eliminada.
     * @return void
     */
    public function delete(Offer $offer): void
    {
        $offer->delete();
    }

    /**
     * Devolve as ofertas criadas por um parceiro específico, ordenadas pela data de início (desc).
     * Permite filtrar opcionalmente por ativas ou inativas.
     *
     * @param Partner $partner Instância do parceiro cujas ofertas queremos listar.
     * @param bool|null $active (Opcional) Filtro de estado: true = ativas, false = inativas, null = todas.
     * @return Collection
     */
    public function listByPartner(Partner $partner, ?bool $active = null): Collection
    {
        return $partner->offers()
            ->when(! is_null($active), fn ($q) => $q->where('active', $active))
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Devolve todas as ofertas ativas no dia de hoje em toda a plataforma.
     * Inclui a relação com o parceiro.
     *
     * @return Collection
     */
    public function listActiveRecommendations(): Collection
    {
        $hoje = now()->toDateString();

        return Offer::where('active', true)
            ->where('start_date', '<=', $hoje)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $hoje))
            ->with('partner')
            ->get();
    }
}