import { getPropertiesPage } from "../services/api/propertyService";
import { usePagedList } from "./usePagedList";

export function useHousesPage({ perPage = 5 } = {}) {
  return usePagedList(
    getPropertiesPage,
    { perPage },
    { errorMessage: "Não foi possível carregar as casas." },
  );
}
