import { useCallback, useEffect, useState } from "react";
import { fetchAllInvoices } from "../services/api/invoiceService";

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
      loading: Boolean(propertyId),
      error: "",
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
        loading: Boolean(nextPropertyId),
        error: "",
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

  // Carregamento das faturas através do serviço centralizado.
  useEffect(() => {
    let cancelled = false;

    const propertyId = state.propertyId;

    // Sem propriedade selecionada, não existem faturas para carregar.
    if (!propertyId) {
      setState((prev) =>
        prev.propertyId === propertyId
          ? {
              ...prev,
              invoices: [],
              loading: false,
              error: "",
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
            error: "",
          }
        : prev
    );

    // Obtém todas as páginas de faturas da propriedade.
    async function loadInvoices() {
      try {
        const invoices = await fetchAllInvoices(propertyId);

        if (!cancelled) {
          setState((prev) =>
            prev.propertyId === propertyId
              ? {
                  ...prev,
                  invoices,
                  loading: false,
                  error: "",
                }
              : prev
          );
        }
      } catch (err) {
        if (!cancelled) {
          setState((prev) =>
            prev.propertyId === propertyId
              ? {
                  ...prev,
                  invoices: [],
                  loading: false,
                  error:
                    err?.response?.data?.message ||
                    "Não foi possível carregar as faturas.",
                }
              : prev
          );
        }
      }
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
    loading: state.loading,
    error: state.error,
    refresh,
  };
}
