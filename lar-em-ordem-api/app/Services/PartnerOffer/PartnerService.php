<?php


namespace App\Services\PartnerOffer;

use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerService
{
    /**
     * Procura o perfil de parceiro de um utilizador.
     *
     * @param User $user Utilizador a procurar.
     * @return Partner|null O parceiro, ou null se o utilizador não tiver perfil de parceiro.
     */
    public function findByUser(User $user): ?Partner
    {
        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Devolve o perfil de parceiro de um utilizador; termina com 403 se não existir.
     *
     * @param User $user Utilizador a procurar.
     * @return Partner
     */
    public function getByUser(User $user): Partner
    {
        return $this->findByUser($user)
            ?? abort(403, 'Este utilizador não tem um perfil de parceiro.');
    }
    
   /**
     * Parceiros por ordem alfabética, paginados, com o nº de ofertas em vigor de cada um
     * (os apagados ficam de fora pelo soft delete).
     *
     * @param int $perPage Número de parceiros por página.
     * @return LengthAwarePaginator
     */
    public function list(int $perPage = 5): LengthAwarePaginator
    {
        return Partner::withCount(['offers as current_offers_count' => fn ($q) => $q->current()])
            ->orderBy('company_name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cria o perfil de parceiro de um utilizador e atribui-lhe a role 'partner'.
     * Cada utilizador só pode ter um perfil de parceiro.
     *
     * @param array $data Dados validados para a criação do parceiro.
     * @param User $owner Utilizador dono do perfil.
     * @return Partner
     */
    public function create(array $data, User $owner): Partner
    {
        if ($this->findByUser($owner)) {
            throw ValidationException::withMessages([
                'user_id' => 'Este utilizador já tem um perfil de parceiro.',
            ]);
        }
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('partner');

            return Partner::create($data + [
                'user_id' => $owner->id,
            ]);
        });
    }

    /**
     * Atualiza os dados de um parceiro existente.
     *
     * @param Partner $partner Instância do parceiro a ser atualizado.
     * @param array $data Dados validados para a atualização.
     * @return Partner
     */
    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);
        return $partner;
    }

    /**
     * Remove um parceiro e as suas ofertas, e retira a role 'partner' ao utilizador.
     * Tudo numa transação: se um passo falhar, nada é alterado.
     *
     * @param Partner $partner Instância do parceiro a ser removido.
     * @return void
     */
   public function delete(Partner $partner): void
    {
        DB::transaction(function () use ($partner) {
            $partner->offers()->delete();
            $partner->delete();
            $partner->user->removeRole('partner');
        });
    }

   /**
     * Carrega no parceiro uma página das suas ofertas em vigor (vista pública),
     * da mais recente para a mais antiga.
     *
     * @param Partner $partner Instância do parceiro.
     * @param int $perPage Número de ofertas por página.
     * @return LengthAwarePaginator A página de ofertas (já colocada em $partner->offers).
     */
    public function loadWithOffers(Partner $partner, int $perPage = 5): LengthAwarePaginator
    {
        $offers = $partner->offers()
            ->current()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // a página de ofertas fica na relação 'offers': o resource mostra-a em data.offers
        $partner->setRelation('offers', $offers->getCollection());

        return $offers;
    }
}