import { useState, useEffect } from "react";
import { getAssistanceRequest } from "../services/api/assistanceService";

function loadError(err) {
  const status = err.response?.status;
  return status === 403 || status === 404
    ? "Este pedido não está disponível."
    : "Não foi possível carregar o pedido.";
}

export function useAssistanceRequest(id) {
  const [request, setRequest] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [version, setVersion] = useState(0);

  useEffect(() => {
    let active = true;
    getAssistanceRequest(id)
      .then((data) => {
        if (active) {
          setRequest(data);
          setError("");
        }
      })
      .catch((err) => {
        if (active) setError(loadError(err));
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [id, version]);

  function reload() {
    setVersion((v) => v + 1);
  }

  return { request, loading, error, reload };
}