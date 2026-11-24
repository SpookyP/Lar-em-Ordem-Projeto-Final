import { getNotices } from "../services/api/condominiumService";
import { usePagedList } from "./usePagedList";

export function useCondominiumNoticesPage(condominiumId, { perPage = 5 } = {}) {
  return usePagedList(
    getNotices,
    { condominiumId, perPage },
    {
      enabled: Boolean(condominiumId),
      errorMessage: "Não foi possível carregar os avisos. Tente de novo dentro de um minuto.",
    },
  );
}