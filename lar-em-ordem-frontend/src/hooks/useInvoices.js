import { useCallback, useEffect, useState } from "react";
import {
  fetchAllInvoices,
  fetchMonthlyCostBenchmarks,
} from "../services/api/invoiceService";

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
  // Estado partilhado pela página que utiliza este hook.
  const [state, setState] = useState(() => {
    const propertyId = getActivePropertyId();

    return {
      propertyId,
      invoices: [],
      benchmarks: [],
      loading: Boolean(propertyId),
      benchmarkLoading: Boolean(propertyId),
      error: "",
      benchmarkError: "",
    };
  });

  const [refreshKey, setRefreshKey] = useState(0);

  // Sincronização com a casa ativa e atualizações das faturas.
  useEffect(() => {
    const syncActiveHouse = () => {
      const nextPropertyId = getActivePropertyId();

      setState((prev) => ({
        propertyId: nextPropertyId,
        invoices:
          prev.propertyId === nextPropertyId
            ? prev.invoices
            : [],
        benchmarks:
          prev.propertyId === nextPropertyId
            ? prev.benchmarks
            : [],
        loading: Boolean(nextPropertyId),
        benchmarkLoading: Boolean(nextPropertyId),
        error: "",
        benchmarkError: "",
      }));

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

  // Carregamento das faturas e dos benchmarks através dos serviços centralizados.
  useEffect(() => {
    let cancelled = false;

    const propertyId = state.propertyId;

    // Sem propriedade selecionada, não existem dados para carregar.
    if (!propertyId) {
      setState((prev) =>
        prev.propertyId === propertyId
          ? {
              ...prev,
              invoices: [],
              benchmarks: [],
              loading: false,
              benchmarkLoading: false,
              error: "",
              benchmarkError: "",
            }
          : prev
      );

      return undefined;
    }

    // Indica que está a decorrer um novo pedido.
    setState((prev) =>
      prev.propertyId === propertyId
        ? {
            ...prev,
            loading: true,
            benchmarkLoading: true,
            error: "",
            benchmarkError: "",
          }
        : prev
    );

    // Obtém as faturas e os benchmarks da propriedade.
    async function loadInvoices() {
      const [invoiceResult, benchmarkResult] = await Promise.allSettled([
        fetchAllInvoices(propertyId),
        fetchMonthlyCostBenchmarks(propertyId),
      ]);

      if (cancelled) return;

      setState((prev) => {
        if (prev.propertyId !== propertyId) return prev;

        return {
          ...prev,
          invoices:
            invoiceResult.status === "fulfilled"
              ? invoiceResult.value
              : [],
          benchmarks:
            benchmarkResult.status === "fulfilled"
              ? benchmarkResult.value
              : [],
          loading: false,
          benchmarkLoading: false,
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
        };
      });
    }

    loadInvoices();

    // Impede que respostas de pedidos anteriores atualizem o estado.
    return () => {
      cancelled = true;
    };
  }, [state.propertyId, refreshKey]);

  // Permite atualizar os dados depois de criar ou eliminar uma fatura.
  const refresh = useCallback(() => {
    setRefreshKey((value) => value + 1);
  }, []);

  // Dados e operações disponibilizados à página.
  return {
    propertyId: state.propertyId,
    invoices: state.invoices,
    benchmarks: state.benchmarks,
    loading: state.loading,
    benchmarkLoading: state.benchmarkLoading,
    error: state.error,
    benchmarkError: state.benchmarkError,
    refresh,
  };
}
