import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import InvoiceCard from "../../../components/invoice/InvoiceCard";
import InvoiceTable from "../../../components/invoice/InvoiceTable";
import useInvoices from "../../../hooks/useInvoices";

export default function Invoice() {
  const navigate = useNavigate();

  const { invoices, loading, error, refresh } = useInvoices();

  return (
    <div className="w-full max-w-none min-w-0 space-y-8 px-3 pb-12 sm:px-4 lg:px-6">
      {/* Cabeçalho e navegação para o formulário de faturas */}
      <div className="flex w-full min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <h1 className="font-display text-3xl text-slate-900">Faturas</h1>

          <p className="mt-2 text-sm text-slate-500">
            Consulta e acompanha as faturas da tua casa.
          </p>
        </div>

        <button
          type="button"
          onClick={() =>
            navigate("/consumos", {
              state: { scrollToInvoiceForm: true },
            })
          }
          className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#1C3557] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#29496F]"
        >
          <Plus size={18} />
          Adicionar Fatura
        </button>
      </div>

      {/* Resumo das despesas */}
      <div className="w-full min-w-0">
        <InvoiceCard invoices={invoices} loading={loading} error={error} />
      </div>

      {/* Histórico, pesquisa, filtros e gestão das faturas */}
      <div className="w-full min-w-0">
        <InvoiceTable
          invoices={invoices}
          loading={loading}
          error={error}
          onRefresh={refresh}
        />
      </div>
    </div>
  );
}
