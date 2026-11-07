import { getRecommendations } from "../services/api/offerService";
import { usePagedList } from "./usePagedList";

export function useOffersPage({
  perPage = 12,
  search = "",
  categoryId = "",
} = {}) {
  return usePagedList(
    getRecommendations,
    { perPage, search, categoryId },
    {
      errorMessage:
        "Não foi possível carregar as ofertas. Tente de novo dentro de um minuto.",
    },
  );
}
