import { getDocumentCategories } from "../services/api/documentService";
import { useFetch } from "./useFetch";

const NO_CATEGORIES = [];

export function useDocumentCategories() {
  const { data, loading, error } = useFetch(getDocumentCategories, [], {
    errorMessage: "Não foi possível carregar os tipos de documento.",
  });

  return { categories: data ?? NO_CATEGORIES, loading, error };
}