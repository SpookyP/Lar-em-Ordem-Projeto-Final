export function getErrorMessage(err, fallback = "Não foi possível concluir o pedido.") {
  if (err.code === "ERR_NETWORK") {
    return "Sem ligação ao servidor. Verifique a internet e tente novamente.";
  }
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
  if (status >= 500) {
    return "O servidor está com problemas. Tente novamente dentro de momentos.";
  }
  return fallback;
}

// turns a 422 response into { field: "first message" }; "zone_ids.0" also fills "zone_ids"
export function getFieldErrors(err) {
  const errors = err?.response?.data?.errors ?? {};
  const result = {};

  Object.entries(errors).forEach(([key, messages]) => {
    const message = Array.isArray(messages) ? messages[0] : String(messages);
    result[key] ??= message;
    result[key.split(".")[0]] ??= message;
  });

  return result;
}