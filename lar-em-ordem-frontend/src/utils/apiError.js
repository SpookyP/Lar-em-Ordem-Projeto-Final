export function getErrorMessage(err, fallback = "Não foi possível concluir o pedido.") {
  const status = err.response?.status;
  if (status === 422) {
    const errors = err.response.data.errors ?? {};
    return (
      Object.values(errors)[0]?.[0] ?? err.response.data.message ?? fallback
    );
  }
  if (status === 403) return "Não tem permissão para esta ação.";
  if (status === 404) return "O registo já não existe.";
  if (status === 429) {
    return "Demasiados pedidos. Tente novamente dentro de um minuto.";
  }
  return fallback;
}