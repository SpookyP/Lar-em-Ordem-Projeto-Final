import { Eye, Download, Trash2 } from "lucide-react";

export function InvoiceActions({
  invoice,
  onDetails,
  onDownload,
  onDelete,
  disabled,
}) {
  return (
    <div className="flex shrink-0 items-center gap-1.5">
      <button
        type="button"
        title="Ver detalhes"
        aria-label={`Ver detalhes da fatura ${invoice.invoice_number || ""}`}
        onClick={() => onDetails(invoice)}
        disabled={disabled}
        className="rounded-lg border border-slate-200 p-2.5 text-slate-600 transition hover:border-[#583D69]/30 hover:bg-[#583D69]/5 hover:text-[#583D69] disabled:opacity-40 xl:border-0 xl:p-2 xl:text-slate-500 xl:hover:bg-[#583D69]/10"
      >
        <Eye size={17} />
      </button>

      <button
        type="button"
        title="Abrir PDF"
        aria-label={`Abrir PDF da fatura ${invoice.invoice_number || ""}`}
        onClick={() => onDownload(invoice)}
        disabled={disabled || !invoice.has_file}
        className="rounded-lg border border-slate-200 p-2.5 text-slate-600 transition hover:border-navy/30 hover:bg-navy/5 hover:text-navy disabled:cursor-not-allowed disabled:opacity-30 xl:border-0 xl:p-2 xl:text-slate-500 xl:hover:bg-navy/10"
      >
        <Download size={17} />
      </button>

      <button
        type="button"
        title="Eliminar fatura"
        aria-label={`Eliminar fatura ${invoice.invoice_number || ""}`}
        onClick={() => onDelete(invoice)}
        disabled={disabled}
        className="rounded-lg border border-slate-200 p-2.5 text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:opacity-40 xl:border-0 xl:p-2 xl:text-slate-500 xl:hover:bg-red-50"
      >
        <Trash2 size={17} />
      </button>
    </div>
  );
}
