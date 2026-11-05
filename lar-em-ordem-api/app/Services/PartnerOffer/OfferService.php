<?php

namespace App\Services\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\Offer\OfferCategory;
use App\Models\User\Partner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OfferService
{
    /**
     * Devolve as ofertas de um parceiro (uso do próprio parceiro), da data de início mais recente
     * para a mais antiga, paginadas. Opcionalmente só as de um estado (Offer::scopeStatus).
     *
     * @param Partner $partner Instância do parceiro cujas ofertas queremos listar.
     * @param int $perPage Número de ofertas por página.
     * @param string|null $status current|scheduled|expired|inactive ou null para todas.
     * @return LengthAwarePaginator
     */
    public function listByPartner(Partner $partner, int $perPage = 5, ?string $status = null): LengthAwarePaginator
    {
        return $partner->offers()
            ->with('category')
            ->when($status, fn ($q) => $q->status($status))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

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
        ])->load('category');
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

        return $offer->load('category');
    }

    /**
     * Remove uma oferta.
     *
     * @param Offer $offer Instância da oferta a ser removida.
     * @return void
     */
    public function delete(Offer $offer): void
    {
        $offer->delete();
    }

    /**
     * Carrega o parceiro e o tipo de uma oferta existente.
     *
     * @param Offer $offer Instância da oferta.
     * @return Offer A mesma instância, com 'partner' e 'category' carregados.
     */
    public function loadWithPartner(Offer $offer): Offer
    {
        return $offer->load(['partner', 'category']);
    }

    /**
     * Devolve as ofertas em vigor hoje de todos os parceiros, com o parceiro e o tipo, paginadas.
     * A pesquisa procura no título, na descrição e no nome do parceiro.
     *
     * @param int $perPage Número de ofertas por página.
     * @param string|null $search Texto a procurar.
     * @param int|null $categoryId Tipo de oferta.
     * @return LengthAwarePaginator
     */
    public function listActiveOffers(int $perPage = 5, ?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Offer::current()
            ->with(['partner', 'category'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('partner', fn ($p) => $p->where('company_name', 'like', "%{$search}%"))))
            ->when($categoryId, fn ($q) => $q->where('offer_category_id', $categoryId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Catálogo de tipos de oferta (filtros das recomendações e formulário do parceiro).
     *
     * @return Collection
     */
    public function categories(): Collection
    {
        return OfferCategory::orderBy('id')->get(['id', 'name']);
    }
}