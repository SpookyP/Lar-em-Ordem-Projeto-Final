import { getAvailableJobs } from "../services/api/jobService";
import { usePagedList } from "./usePagedList";

export function useAvailableJobs({ search, location }) {
  return usePagedList(
    getAvailableJobs,
    { search, location, perPage: 10 },
    { errorMessage: "Não foi possível carregar os pedidos." },
  );
}