import { useCallback, useEffect, useState } from "react";
import {
  fetchAllInvoices,
  fetchMonthlyCostBenchmarks,
} from "../services/api/invoiceService";

// Referências estáveis para evitar novos arrays (e resets nos filhos) a cada render.
const NO_INVOICES = [];
const NO_BENCHMARKS = [];

// Obtém o ID da propriedade atualmente selecionada.
function getActivePropertyId() {
  if (typeof window === "undefined") return "";

  try {
    const stored = window.localStorage.getItem("activeHouse");

    if (!stored) return "";

    try {
      const parsed = JSON.parse(stored);

      // Aceita o ID guardado diretamente como string.
      if (typeof parsed === "string") return parsed;

      // Aceita também um ID numérico (ex.: "5" é lido como número pelo JSON.parse).
      if (typeof parsed === "number") return String(parsed);

      // Aceita também um objeto com a propriedade id.
      if (parsed && typeof parsed === "object") {
        return parsed.id ?? "";
      }

      return "";
    } catch {
      return stored;
    }
  } catch {
    // Evita erros caso o localStorage não esteja disponível.
    return "";
  }
}

export default function useInvoices() {
  const [propertyId, setPropertyId] = useState(getActivePropertyId);
  const [refreshKey, setRefreshKey] = useState(0);

  // Resultado do último pedido concluído (identificado pela chave do pedido).
  const [fetched, setFetched] = useState({
    key: null,
    propertyId: null,
    invoices: [],
    benchmarks: [],
    error: "",
    benchmarkError: "",
  });

  // Sincronização com a casa ativa e atualizações das faturas.
  useEffect(() => {
    const syncActiveHouse = () => {
      setPropertyId(getActivePropertyId());

      // Volta a carregar os dados da propriedade selecionada.
      setRefreshKey((value) => value + 1);
    };

    // O evento storage só interessa quando muda a casa ativa.
    const handleStorage = (event) => {
      if (event.key !== "activeHouse" && event.key !== null) {
        return;
      }

      syncActiveHouse();
    };

    // Evento personalizado utilizado na mesma janela.
    const handleInvoicesUpdated = () => {
      setRefreshKey((value) => value + 1);
    };

    window.addEventListener("activeHouseChanged", syncActiveHouse);
    window.addEventListener("storage", handleStorage);
    window.addEventListener("invoicesUpdated", handleInvoicesUpdated);

    return () => {
      window.removeEventListener("activeHouseChanged", syncActiveHouse);
      window.removeEventListener("storage", handleStorage);
      window.removeEventListener("invoicesUpdated", handleInvoicesUpdated);
    };
  }, []);

  const requestKey = `${propertyId}-${refreshKey}`;

  // Carregamento das faturas e dos benchmarks através dos serviços centralizados.
  useEffect(() => {
    // Sem propriedade selecionada, não existem dados para carregar.
    if (!propertyId) return undefined;

    // Impede que respostas de pedidos anteriores atualizem o estado.
    let cancelled = false;

    Promise.allSettled([
      fetchAllInvoices(propertyId),
      fetchMonthlyCostBenchmarks(propertyId),
    ]).then(([invoiceResult, benchmarkResult]) => {
      if (cancelled) return;

      setFetched({
        key: requestKey,
        propertyId,
        invoices:
          invoiceResult.status === "fulfilled" ? invoiceResult.value : [],
        benchmarks:
          benchmarkResult.status === "fulfilled" ? benchmarkResult.value : [],
        error:
          invoiceResult.status === "rejected"
            ? invoiceResult.reason?.response?.data?.message ||
              "Não foi possível carregar as faturas."
            : "",
        benchmarkError:
          benchmarkResult.status === "rejected"
            ? benchmarkResult.reason?.response?.data?.message ||
              "Não foi possível carregar os benchmarks de custos."
            : "",
      });
    });

    return () => {
      cancelled = true;
    };
  }, [propertyId, requestKey]);

  // Valores derivados do último pedido (sem estado duplicado).
  const isCurrent = fetched.key === requestKey;
  const loading = Boolean(propertyId) && !isCurrent;

  // Durante uma atualização da mesma casa mantêm-se os dados anteriores.
  const sameProperty = Boolean(propertyId) && fetched.propertyId === propertyId;

  // Permite atualizar os dados depois de criar ou eliminar uma fatura.
  const refresh = useCallback(() => {
    setRefreshKey((value) => value + 1);
  }, []);

  // Dados e operações disponibilizados à página.
  return {
    propertyId,
    invoices: sameProperty ? fetched.invoices : NO_INVOICES,
    benchmarks: sameProperty ? fetched.benchmarks : NO_BENCHMARKS,
    loading,
    benchmarkLoading: loading,
    error: isCurrent ? fetched.error : "",
    benchmarkError: isCurrent ? fetched.benchmarkError : "",
    refresh,
  };
}
