<?php


namespace App\Services\PartnerOffer;

use App\Models\User\Partner;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Support\Search;

class PartnerService
{
    public function __construct(private PersonalDataEraser $eraser) {}
    
    /**
     * Partners in alphabetical order, paginated, each with their number of current offers,
     * optionally filtered by text in the name or description.
     * Deleted partners are left out by the soft delete.
     *
     * @param int $perPage Partners per page.
     * @param string|null $search (Optional) Text to search in the name or description.
     * @return LengthAwarePaginator
     */
    public function list(int $perPage = 5, ?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Partner::withCount(['offers as current_offers_count' => fn ($q) => $q->current()])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('company_name', 'like', Search::contains($search))
                ->orWhere('description', 'like', Search::contains($search))))
            ->orderBy('company_name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Creates a user's partner profile and assigns the 'partner' role.
     *
     * @param array $data Validated data for the new partner.
     * @param User $owner User who owns the profile.
     * @return Partner
     */
    public function create(array $data, User $owner): Partner
    {
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('partner');

            return Partner::create($data + [
                'user_id' => $owner->id,
            ]);
        });
    }

    /**
     * Updates an existing partner.
     *
     * @param Partner $partner Partner to update.
     * @param array $data Validated data for the update.
     * @return Partner
     */
    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);
        return $partner;
    }

    /**
     * Removes a partner and their offers, and takes the 'partner' role from the user.
     * All in one transaction: if a step fails, nothing changes.
     *
     * @param Partner $partner Partner to remove.
     * @return void
     */
   public function delete(Partner $partner): void
    {
        DB::transaction(function () use ($partner) {
            $partner->offers()->delete();
            $this->eraser->erasePartner($partner);
            $partner->delete();
            $partner->user->removeRole('partner');
        });
    }

   /**
    * Loads one page of the partner's current offers onto the partner (public view),
    * newest first.
    *
    * @param Partner $partner The partner.
    * @param int $perPage Offers per page.
    * @return LengthAwarePaginator The page of offers (already set on $partner->offers).
    */
    public function loadWithOffers(Partner $partner, int $perPage = 5): LengthAwarePaginator
    {
        $offers = $partner->offers()
            ->current()
            ->with('category')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // the page of offers goes in the 'offers' relation: the resource shows it in data.offers
        $partner->setRelation('offers', $offers->getCollection());

        return $offers;
    }
}