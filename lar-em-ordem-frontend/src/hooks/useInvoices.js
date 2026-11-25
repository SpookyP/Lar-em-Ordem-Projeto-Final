import { useCallback, useEffect, useState } from "react";
import {
  fetchAllInvoices,
  fetchMonthlyCostBenchmarks,
} from "../services/api/invoiceService";

// Stable references so a new array (and a reset in the children) isn't created on every render.
const NO_INVOICES = [];
const NO_BENCHMARKS = [];

// Gets the id of the currently selected property.
function getActivePropertyId() {
  if (typeof window === "undefined") return "";

  try {
    const stored = window.localStorage.getItem("activeHouse");

    if (!stored) return "";

    try {
      const parsed = JSON.parse(stored);

      // Accepts the stored id as a plain string.
      if (typeof parsed === "string") return parsed;

      // Also accepts a numeric id (e.g. "5" is read as a number by JSON.parse).
      if (typeof parsed === "number") return String(parsed);

      // Also accepts an object with an id property.
      if (parsed && typeof parsed === "object") {
        return parsed.id ?? "";
      }

      return "";
    } catch {
      return stored;
    }
  } catch {
    // Avoids errors when localStorage isn't available.
    return "";
  }
}

export default function useInvoices() {
  const [propertyId, setPropertyId] = useState(getActivePropertyId);
  const [refreshKey, setRefreshKey] = useState(0);

  // Result of the last finished request (identified by the request key).
  const [fetched, setFetched] = useState({
    key: null,
    propertyId: null,
    invoices: [],
    benchmarks: [],
    error: "",
    benchmarkError: "",
  });

  // Keeps in sync with the active property and invoice updates.
  useEffect(() => {
    const syncActiveHouse = () => {
      setPropertyId(getActivePropertyId());

      // Reloads the selected property's data.
      setRefreshKey((value) => value + 1);
    };

    // The storage event only matters when the active property changes.
    const handleStorage = (event) => {
      if (event.key !== "activeHouse" && event.key !== null) {
        return;
      }

      syncActiveHouse();
    };

    // Custom event used in the same window.
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

  // Loads the invoices and benchmarks through the shared services.
  useEffect(() => {
    // No property selected, so there is nothing to load.
    if (!propertyId) return undefined;

    // Stops responses from older requests from updating the state.
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

  // Values derived from the last request (no duplicated state).
  const isCurrent = fetched.key === requestKey;
  const loading = Boolean(propertyId) && !isCurrent;

  // While the same property refreshes, the previous data stays.
  const sameProperty = Boolean(propertyId) && fetched.propertyId === propertyId;

  // Lets the data refresh after an invoice is created or deleted.
  const refresh = useCallback(() => {
    setRefreshKey((value) => value + 1);
  }, []);

  // Data and actions handed to the page.
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
