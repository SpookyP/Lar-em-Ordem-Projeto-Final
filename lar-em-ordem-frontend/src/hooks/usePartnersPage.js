import { getPartnersPage } from "../services/api/partnerService";
import { usePagedList } from "./usePagedList";

export function usePartnersPage({ perPage = 12, search = "" } = {}) {
  return usePagedList(
    getPartnersPage,
    { perPage, search },
    {
      errorMessage:
        "Não foi possível carregar os parceiros. Tente de novo dentro de um minuto.",
    },
  );
}
