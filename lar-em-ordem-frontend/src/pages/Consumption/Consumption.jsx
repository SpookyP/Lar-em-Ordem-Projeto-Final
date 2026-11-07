import { useState, useEffect, useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import useInvoices from "../../hooks/useInvoices";
import DetailedConsumptionChart from "../../components/ui/DetailedConsumptionChart";
import InvoiceTimelineChart, {
  scaleInvoicesToRange,
} from "../../components/ui/InvoiceTimelineChart";
import InvoiceForm from "../../components/ui/InvoiceForm";

// Abreviaturas dos meses utilizadas no cabeçalho.
const MONTHS = [
  "Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
  "Jul", "Ago", "Set", "Out", "Nov", "Dez",
];

// Converte uma chave YYYY-MM para uma etiqueta legível.
const formatMonth = (key) => {
  if (!key || typeof key !== "string") return "";

  const monthIndex = Number(key.slice(5, 7)) - 1;
  const year = key.slice(0, 4);

  if (monthIndex < 0 || monthIndex > 11) return key;

  return `${MONTHS[monthIndex]} ${year}`;
};

export default function Consumption() {
  const location = useLocation();
  const navigate = useNavigate();

  // Obtém os dados das faturas através do hook centralizado.
  const {
    propertyId,
    invoices,
    loading,
    error,
    refresh,
  } = useInvoices();

  // O intervalo selecionado fica associado à casa ativa.
  const [rangeState, setRangeState] = useState({
    propertyId: null,
    range: null,
  });

  // Só utiliza o intervalo guardado quando pertence à casa atual.
  const range =
    rangeState.propertyId === propertyId
      ? rangeState.range
      : null;

  // Atualiza o intervalo selecionado pelos gráficos.
  const handleRangeChange = (nextRange) => {
    setRangeState({
      propertyId,
      range: nextRange,
    });
  };

  // Filtro de serviço partilhado pelos dois gráficos.
  const [serviceFilter, setServiceFilter] = useState("all");

  // Ajusta as faturas ao intervalo temporal selecionado.
  const visibleInvoices = useMemo(
    () => scaleInvoicesToRange(invoices, range),
    [invoices, range]
  );

  // Scroll para o formulário depois do carregamento das faturas.
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

      // Limpa o estado de navegação sem perder a query string ou o hash.
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

      {/* Cabeçalho da página. */}
      <div className="min-w-0">
        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 break-words">
          Consumos Inteligentes
        </h1>

        <p className="text-slate-500 text-xs sm:text-sm mt-1 break-words">
          Decomposição das faturas
          {range
            ? ` · ${formatMonth(range.start)} – ${formatMonth(range.end)}`
            : ""}
        </p>
      </div>

      {/* Apresentação de eventuais erros no carregamento. */}
      {error && (
        <p className="min-w-0 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 break-words">
          {error}
        </p>
      )}

      {/* Estado de carregamento e apresentação dos gráficos. */}
      {loading ? (
        <p className="py-10 text-center text-sm text-slate-500">
          A carregar consumos...
        </p>
      ) : (
        <div className="w-full min-w-0 space-y-6 py-2 sm:py-4">

          {/* Gráfico detalhado dos consumos por serviço e artigo. */}
          <div className="w-full min-w-0">
            <DetailedConsumptionChart
              invoices={visibleInvoices}
              filter={serviceFilter}
              onFilterChange={setServiceFilter}
            />
          </div>

          {/* Linha temporal dos custos mensais. */}
          <div className="w-full min-w-0">
            <InvoiceTimelineChart
              key={propertyId ?? "no-property"}
              invoices={invoices}
              range={range}
              onRangeChange={handleRangeChange}
              serviceFilter={serviceFilter}
            />
          </div>

        </div>
      )}

      {/* Formulário de criação e importação de faturas. */}
      <div
        id="invoice-form"
        className="w-full min-w-0 scroll-mt-6"
      >
        <InvoiceForm onInvoiceSaved={refresh} />
      </div>

    </div>
  );
}
