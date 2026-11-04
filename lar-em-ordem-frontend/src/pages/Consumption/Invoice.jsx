import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import InvoiceCard from "../../components/ui/InvoiceCard";
import InvoiceTable from "../../components/ui/InvoiceTable";

export default function Invoice() {
  const navigate = useNavigate();

  return (
    <div className="w-full min-w-0 space-y-8 pb-12">
      <div className="mx-auto w-full min-w-0 max-w-7xl space-y-8">

        {/* Cabeçalho */}
        <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <h1 className="text-3xl font-bold text-slate-900">
              Faturas
            </h1>

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
            className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"
          >
            <Plus size={18} />
            Adicionar Fatura
          </button>
        </div>

        {/* Resumo das faturas */}
        <div className="w-full min-w-0">
          <InvoiceCard />
        </div>
            
        {/* Histórico de faturas */}
        <div className="w-full min-w-0">
          <InvoiceTable />
        </div>

      </div>
    </div>
  );
}
