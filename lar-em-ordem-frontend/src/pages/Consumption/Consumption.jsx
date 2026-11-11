
import { useState, useEffect, useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import useInvoices from "../../hooks/useInvoices";
import DetailedConsumptionChart from "../../components/ui/DetailedConsumptionChart";
import InvoiceTimelineChart, {
  scaleInvoicesToRange,
} from "../../components/ui/InvoiceTimelineChart";
import AnnualBenchmarkTable from "../../components/ui/AnnualBenchmarkTable";
import InvoiceForm from "../../components/ui/InvoiceForm";

const MONTHS = [
  "Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
  "Jul", "Ago", "Set", "Out", "Nov", "Dez",
];

const formatMonth = (key) => {
  if (!key || typeof key !== "string") return "";

  const monthIndex = Number(key.slice(5, 7)) - 1;
  const year = key.slice(0, 4);

  if (monthIndex < 0 || monthIndex > 11) return key;

  return `${MONTHS[monthIndex]} ${year}`;
};

export default function Consumptions() {
  const location = useLocation();
  const navigate = useNavigate();

  const {
    propertyId,
    invoices,
    benchmarks,
    loading,
    error,
    refresh,
  } = useInvoices();

  const [rangeState, setRangeState] = useState({
    propertyId: null,
    range: null,
  });

  const range =
    rangeState.propertyId === propertyId
      ? rangeState.range
      : null;

  const handleRangeChange = (nextRange) => {
    setRangeState({
      propertyId,
      range: nextRange,
    });
  };

  const [serviceFilter, setServiceFilter] = useState("all");

  // Permite atualizar a tabela anual depois de guardar uma fatura.
  const [annualRefreshKey, setAnnualRefreshKey] = useState(0);

  const handleInvoiceSaved = () => {
    refresh();
    setAnnualRefreshKey((current) => current + 1);
  };

  const visibleInvoices = useMemo(
    () => scaleInvoicesToRange(invoices, range),
    [invoices, range]
  );

  useEffect(() => {
    if (!location.state?.scrollToInvoiceForm) return;
    if (loading) return;

    const timeout = setTimeout(() => {
      const invoiceForm = document.getElementById("invoice-form");

      if (invoiceForm) {
        invoiceForm.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }

      navigate(
        {
          pathname: location.pathname,
          search: location.search,
          hash: location.hash,
        },
        {
          replace: true,
          state: null,
        }
      );
    }, 300);

    return () => clearTimeout(timeout);
  }, [
    location.state?.scrollToInvoiceForm,
    location.pathname,
    location.search,
    location.hash,
    navigate,
    loading,
  ]);

  return (
    <div className="w-full min-w-0 max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 space-y-6 sm:space-y-8 pb-12 overflow-x-hidden">

      <div className="min-w-0">
        <h1 className="font-display text-3xl text-slate-900 break-words">
          Consumos Inteligentes
        </h1>

        <p className="text-slate-500 text-xs sm:text-sm mt-1 break-words">
          Decomposição das faturas
          {range
            ? ` · ${formatMonth(range.start)} – ${formatMonth(range.end)}`
            : ""}
        </p>
      </div>

      {error && (
        <p className="min-w-0 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 break-words">
          {error}
        </p>
      )}

      {loading ? (
        <p className="py-10 text-center text-sm text-slate-500">
          A carregar consumos...
        </p>
      ) : (
        <div className="w-full min-w-0 space-y-6 py-2 sm:py-4">

          <div className="w-full min-w-0">
            <DetailedConsumptionChart
              invoices={visibleInvoices}
              filter={serviceFilter}
              onFilterChange={setServiceFilter}
            />
          </div>

          <div className="w-full min-w-0">
            <InvoiceTimelineChart
              key={propertyId ?? "no-property"}
              invoices={invoices}
              benchmark={benchmarks}
              range={range}
              onRangeChange={handleRangeChange}
              serviceFilter={serviceFilter}
            />
          </div>

        </div>
      )}

      {/* Comparação dos custos anuais com os benchmarks */}
      <div className="w-full min-w-0">
        <AnnualBenchmarkTable
          propertyId={propertyId}
          refreshKey={annualRefreshKey}
        />
      </div>

      {/* Formulário para adicionar novas faturas */}
      <div
        id="invoice-form"
        className="w-full min-w-0 scroll-mt-6"
      >
        <InvoiceForm onInvoiceSaved={handleInvoiceSaved} />
      </div>

    </div>
  );
}
