import { getCondominiumDocuments } from "../services/api/condominiumService";
import { usePagedList } from "./usePagedList";

export function useCondominiumDocumentsPage(condominiumId, { categoryId = "", perPage = 5 } = {}) {
  return usePagedList(
    getCondominiumDocuments,
    { condominiumId, categoryId, perPage },
    {
      enabled: Boolean(condominiumId),
      errorMessage: "Não foi possível carregar os documentos. Tenta de novo dentro de um minuto.",
    },
  );
}