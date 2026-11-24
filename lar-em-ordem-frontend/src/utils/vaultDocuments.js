// documents about to expire or already expired
export function summarizeDocuments(documents) {
  return {
    total: documents.length,
    attention: documents.filter((doc) => doc.status !== "valid").length,
  };
}

export function getVaultSummaryText({ loading, error, summary }) {
  if (loading) return "A carregar documentos…";
  if (error) return "Não foi possível carregar o resumo.";

  const { total, attention } = summary;
  const archived = total === 1 ? "documento arquivado" : "documentos arquivados";

  return `${total} ${archived} · ${attention} a precisar de atenção`;
}