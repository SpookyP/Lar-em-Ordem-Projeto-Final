import { getMyOffers } from "../services/api/offerService";
import { usePagedList } from "./usePagedList";

export function useMyOffersPage({ perPage = 8, status = "" } = {}) {
  return usePagedList(
    getMyOffers,
    { perPage, status },
    {
      errorMessage:
        "Não foi possível carregar as ofertas. Tente de novo dentro de um minuto.",
    },
  );
}
