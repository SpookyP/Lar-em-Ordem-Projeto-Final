<?php

namespace App\Services\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\Offer\OfferCategory;
use App\Models\User\Partner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Support\Search;

class OfferService
{
    /**
     * Returns a partner's own offers, newest start date first, paginated.
     * Optionally only the offers in one status (Offer::scopeStatus).
     *
     * @param Partner $partner Partner whose offers to list.
     * @param int $perPage Offers per page.
     * @param string|null $status current|scheduled|expired|inactive, or null for all.
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
     * Creates a new offer, linked to the partner's id.
     *
     * @param array $data Validated data for the new offer.
     * @param Partner $partner Partner creating the offer.
     * @return Offer
     */
    public function create(array $data, Partner $partner): Offer
    {
        return Offer::create($data + [
            'partner_id' => $partner->id,
        ])->load('category');
    }

    /**
     * Updates an existing offer.
     *
     * @param Offer $offer Offer to update.
     * @param array $data Validated data for the update.
     * @return Offer
     */
    public function update(Offer $offer, array $data): Offer
    {
        $offer->update($data);

        return $offer->load('category');
    }

    /**
     * Removes an offer.
     *
     * @param Offer $offer Offer to remove.
     * @return void
     */
    public function delete(Offer $offer): void
    {
        $offer->delete();
    }

    /**
     * Loads an existing offer's partner and type.
     *
     * @param Offer $offer The offer.
     * @return Offer The same instance, with 'partner' and 'category' loaded.
     */
    public function loadWithPartner(Offer $offer): Offer
    {
        return $offer->load(['partner', 'category']);
    }

    /**
     * Returns today's current offers of every partner, with the partner and the type, paginated.
     * The search looks in the title, the description and the partner's name.
     *
     * @param int $perPage Offers per page.
     * @param string|null $search Text to search for.
     * @param int|null $categoryId Offer type.
     * @return LengthAwarePaginator
     */
    public function listActiveOffers(int $perPage = 5, ?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Offer::current()
            ->with(['partner', 'category'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', Search::contains($search))
                ->orWhere('description', 'like', Search::contains($search))
                ->orWhereHas('partner', fn ($p) => $p->where('company_name', 'like', Search::contains($search)))))
            ->when($categoryId, fn ($q) => $q->where('offer_category_id', $categoryId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Offer type catalog (recommendation filters and the partner form).
     *
     * @return Collection
     */
    public function categories(): Collection
    {
        return OfferCategory::orderBy('id')->get(['id', 'name']);
    }
}