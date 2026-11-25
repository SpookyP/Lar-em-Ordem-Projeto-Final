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
      {/* Header and link to the invoice form */}
      <div className="flex w-full min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <h1 className="font-display text-4xl text-navy">Faturas</h1>

          <p className="mt-1 text-muted">
            Consulte e acompanhe as faturas da sua casa.
          </p>
        </div>

        <button
          type="button"
          onClick={() =>
            navigate("/consumos", {
              state: { scrollToInvoiceForm: true },
            })
          }
          className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-navy px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#29496F]"
        >
          <Plus size={18} />
          Adicionar Fatura
        </button>
      </div>

      {/* Spending summary */}
      <div className="w-full min-w-0">
        <InvoiceCard invoices={invoices} loading={loading} error={error} />
      </div>

      {/* History, search, filters and invoice management */}
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
