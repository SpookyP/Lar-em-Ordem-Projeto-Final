import { useMemo } from "react";
import { getAllPropertyDocuments } from "../services/api/documentService";
import { summarizeDocuments } from "../utils/vaultDocuments";
import { useFetch } from "./useFetch";

const NO_DOCUMENTS = [];

export function useVaultDocuments(propertyId) {
  const { data, loading, error, reload } = useFetch(
    () => getAllPropertyDocuments(propertyId),
    [propertyId],
    {
      enabled: Boolean(propertyId),
      errorMessage: "Não foi possível carregar os documentos.",
    },
  );

  const documents = data ?? NO_DOCUMENTS;
  const summary = useMemo(() => summarizeDocuments(documents), [documents]);

  return { documents, summary, loading, error, reload };
}