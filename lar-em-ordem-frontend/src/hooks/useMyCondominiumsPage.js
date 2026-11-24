import { getMyCondominiums } from "../services/api/condominiumService";
import { usePagedList } from "./usePagedList";

export function useMyCondominiumsPage({ perPage = 6 } = {}) {
  return usePagedList(
    getMyCondominiums,
    { perPage },
    {
      errorMessage:
        "Não foi possível carregar os condomínios. Tente de novo dentro de um minuto.",
    },
  );
}