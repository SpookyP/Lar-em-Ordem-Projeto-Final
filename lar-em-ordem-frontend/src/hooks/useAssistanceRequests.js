import { getAssistanceRequests } from "../services/api/assistanceService";
import { usePagedList } from "./usePagedList";

export function useAssistanceRequests(propertyId, status = "") {
  return usePagedList(
    getAssistanceRequests,
    { propertyId, perPage: 5, status: status || undefined },
    {
      enabled: Boolean(propertyId),
      errorMessage: "Não foi possível carregar os pedidos.",
    },
  );
}
