import { getMyProposals } from "../services/api/proposalService";
import { usePagedList } from "./usePagedList";

export function useMyProposals(status) {
  return usePagedList(
    getMyProposals,
    { status: status === "all" ? undefined : status, perPage: 5 },
    { errorMessage: "Não foi possível carregar as propostas." },
  );
}