import { getAssistanceRequests } from "../services/api/assistanceService";
import { usePagedList } from "./usePagedList";

export function useAssistanceRequests(propertyId) {
  return usePagedList(
    getAssistanceRequests,
    { propertyId, perPage: 5 },
    {
      enabled: Boolean(propertyId),
      errorMessage: "Não foi possível carregar os pedidos.",
    },
  );
}