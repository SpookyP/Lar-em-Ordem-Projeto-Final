import { useEffect } from "react";
import { AlertTriangle, X } from "lucide-react";
import { formatCurrency } from "./invoiceShared";

export function DeleteConfirmationModal({
  invoice,
  confirmed,
  loading,
  onConfirmedChange,
  onClose,
  onConfirm,
}) {
  useEffect(() => {
    if (!invoice || loading) return undefined;

    const handleKeyDown = (event) => {
      if (event.key === "Escape") onClose();
    };

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [invoice, loading, onClose]);

  if (!invoice) return null;

  return (
    <div
      role="alertdialog"
      aria-modal="true"
      aria-label="Eliminar fatura"
      className="fixed inset-0 z-60 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
    >
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-5 flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div className="rounded-xl bg-red-50 p-3 text-red-600">
              <AlertTriangle size={23} />
            </div>
            <div>
              <h3 className="text-lg font-bold text-slate-900">
                Eliminar fatura
              </h3>
              <p className="mt-1 text-sm text-slate-500">
                Confirme que pretende continuar.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            aria-label="Fechar confirmação"
            className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
          >
            <X size={19} />
          </button>
        </div>

        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p className="text-sm text-slate-500">Fatura selecionada</p>
          <p className="mt-1 font-semibold text-slate-900">
            {invoice.invoice_number || "Sem número"}
          </p>
          <p className="mt-1 text-sm text-slate-600">
            {invoice.supplier || "Fornecedor não identificado"}
          </p>
          <p className="mt-2 font-bold text-slate-900">
            {formatCurrency(invoice.total_amount)}
          </p>
        </div>

        <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-red-200 hover:bg-red-50/40">
          <input
            type="checkbox"
            checked={confirmed}
            onChange={(event) => onConfirmedChange(event.target.checked)}
            disabled={loading}
            className="mt-0.5 h-5 w-5 shrink-0 cursor-pointer accent-red-600"
          />
          <span className="text-sm leading-5 text-slate-700">
            Confirmo que quero eliminar esta fatura e compreendo que esta ação
            não pode ser desfeita.
          </span>
        </label>

        <div className="mt-6 flex gap-3">
          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            className="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={!confirmed || loading}
            className="flex-1 rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300"
          >
            {loading ? "A eliminar..." : "Eliminar fatura"}
          </button>
        </div>
      </div>
    </div>
  );
}
