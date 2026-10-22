import { useState, useEffect } from "react";
import { getAssistanceRequests } from "../services/api/assistanceService";

export function useAssistanceRequests() {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getAssistanceRequests()
      .then((list) => {
        if (active) setRequests(list);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar os pedidos.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  function addRequest(created) {
    setRequests((prev) => [created, ...prev]);
  }

  return { requests, loading, error, addRequest };
}