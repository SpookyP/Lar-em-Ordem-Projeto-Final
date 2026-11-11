import { getPropertyDocuments } from "../services/api/condominiumService";
import { usePagedList } from "./usePagedList";

// condominium documents seen by a resident, through the active home
export function usePropertyDocumentsPage(propertyId, { categoryId = "", perPage = 5 } = {}) {
  return usePagedList(
    getPropertyDocuments,
    { propertyId, categoryId, perPage },
    {
      enabled: Boolean(propertyId),
      errorMessage: "Não foi possível carregar os documentos. Tenta de novo dentro de um minuto.",
    },
  );
}