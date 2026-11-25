import { useEffect } from "react";
import { FileText, X } from "lucide-react";
import {
  HEADER_COLOR,
  HEADER_DARK_COLOR,
  SERVICE_TYPES,
  DARKER_SERVICE_COLORS,
  getInvoiceTypes,
  formatCurrency,
  formatDate,
} from "./invoiceShared";
import {
  asNumber as toNumber,
  getConsumptions,
  getItems as getConsumptionItems,
  getTypeId,
} from "../../utils/invoiceTimelineUtils";

export function InvoiceDetailsModal({ invoice, onClose }) {
  useEffect(() => {
    if (!invoice) return undefined;

    const handleKeyDown = (event) => {
      if (event.key === "Escape") onClose();
    };

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [invoice, onClose]);

  if (!invoice) return null;

  const invoiceTypes = getInvoiceTypes(invoice);
  const primaryService = SERVICE_TYPES[invoiceTypes[0]];
  const HeaderIcon = primaryService?.icon || FileText;
  const consumptions = getConsumptions(invoice);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Detalhes da fatura"
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      onClick={onClose}
    >
      <div
        className="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        onClick={(event) => event.stopPropagation()}
      >
        {/* Modal header, always in the main blue. */}
        <div
          className="relative overflow-hidden p-6"
          style={{ backgroundColor: HEADER_COLOR }}
        >
          <div
            className="pointer-events-none absolute -right-8 -top-12 h-44 w-44 rounded-full opacity-20"
            style={{ backgroundColor: HEADER_DARK_COLOR }}
          />
          <div
            className="pointer-events-none absolute -bottom-16 right-28 h-36 w-36 rounded-full opacity-20"
            style={{ backgroundColor: HEADER_DARK_COLOR }}
          />

          <div className="relative flex items-start justify-between gap-4">
            <div className="flex min-w-0 items-center gap-4">
              <div
                className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm"
                style={{ backgroundColor: HEADER_DARK_COLOR }}
              >
                <HeaderIcon size={27} />
              </div>
              <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-white/70">
                  Histórico de faturação
                </p>
                <h3 className="mt-1 text-xl font-bold text-white">
                  Detalhes da fatura
                </h3>
                <p className="mt-1 truncate text-sm text-white/80">
                  {invoice.invoice_number || "Sem número de fatura"}
                </p>
              </div>
            </div>

            {/* Close button (the modal also closes with Escape or by clicking outside). */}
            <button
              type="button"
              onClick={onClose}
              aria-label="Fechar detalhes da fatura"
              className="shrink-0 rounded-xl bg-white/10 p-2 text-white transition hover:bg-white/20"
            >
              <X size={20} />
            </button>
          </div>

          {/* Services on the invoice. */}
          <div className="relative mt-5 flex flex-wrap gap-2">
            {invoiceTypes.length > 0 ? (
              invoiceTypes.map((typeId) => {
                const service = SERVICE_TYPES[typeId];
                const Icon = service.icon;

                return (
                  <span
                    key={typeId}
                    className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white"
                  >
                    <Icon size={14} />
                    {service.name}
                  </span>
                );
              })
            ) : (
              <span className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                <FileText size={14} />
                Serviço não identificado
              </span>
            )}
          </div>
        </div>

        {/* Modal content. */}
        <div className="max-h-[calc(85vh-150px)] w-full min-w-0 space-y-6 overflow-y-auto p-5 sm:p-6">
          {/* Main invoice details. */}
          <section>
            <h4 className="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">
              Informação da fatura
            </h4>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p className="text-xs font-medium text-slate-500">Fornecedor</p>
                <p className="mt-1 wrap-break-word font-semibold text-slate-900">
                  {invoice.supplier || "—"}
                </p>
              </div>
              <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p className="text-xs font-medium text-slate-500">
                  Número da fatura
                </p>
                <p className="mt-1 wrap-break-word font-semibold text-slate-900">
                  {invoice.invoice_number || "—"}
                </p>
              </div>
              <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p className="text-xs font-medium text-slate-500">
                  Data de emissão
                </p>
                <p className="mt-1 font-semibold text-slate-900">
                  {formatDate(invoice.issue_date)}
                </p>
              </div>
              <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p className="text-xs font-medium text-slate-500">
                  Período de faturação
                </p>
                <p className="mt-1 font-semibold text-slate-900">
                  {formatDate(invoice.period_start)}
                  <span className="mx-1.5 text-slate-400">—</span>
                  {formatDate(invoice.period_end)}
                </p>
              </div>
            </div>
          </section>

          {/* Consumptions on the invoice. */}
          <section>
            <div className="mb-3 flex items-center justify-between gap-3">
              <h4 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                Consumos associados
              </h4>
              <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                {consumptions.length}{" "}
                {consumptions.length === 1 ? "consumo" : "consumos"}
              </span>
            </div>

            {consumptions.length === 0 ? (
              <div className="rounded-xl border border-dashed border-slate-200 p-5 text-center">
                <FileText className="mx-auto mb-2 text-slate-300" size={24} />
                <p className="text-sm text-slate-500">
                  Não existem consumos associados a esta fatura.
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {consumptions.map((consumption, index) => {
                  const typeId = getTypeId(consumption);
                  const service = SERVICE_TYPES[typeId];
                  const Icon = service?.icon || FileText;
                  const serviceColor = service?.color || "#64748B";
                  const darkerColor =
                    DARKER_SERVICE_COLORS[typeId] || "#475569";
                  const items = getConsumptionItems(consumption);

                  return (
                    <article
                      key={consumption.id || `${typeId || "service"}-${index}`}
                      className="overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-sm"
                    >
                      <div className="flex items-center justify-between gap-3 p-4">
                        <div className="flex min-w-0 items-center gap-3">
                          <div
                            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                            style={{ backgroundColor: serviceColor }}
                          >
                            <Icon size={19} />
                          </div>
                          <div className="min-w-0">
                            <p className="font-semibold text-slate-800">
                              {service?.name || "Consumo"}
                            </p>
                            <p className="mt-1 text-xs text-slate-500">
                              {formatDate(consumption.period_start)}
                              {" — "}
                              {formatDate(consumption.period_end)}
                            </p>
                          </div>
                        </div>
                        <div className="shrink-0 text-right">
                          <p className="text-xs text-slate-500">Custo</p>
                          <p className="mt-0.5 font-bold text-slate-900">
                            {formatCurrency(consumption.cost)}
                          </p>
                        </div>
                      </div>

                      {items.length > 0 && (
                        <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                          <p
                            className="mb-2 text-xs font-semibold uppercase tracking-wider"
                            style={{ color: darkerColor }}
                          >
                            Detalhe do consumo
                          </p>
                          <div className="space-y-2">
                            {items.map((item, itemIndex) => (
                              <div
                                key={item.id || itemIndex}
                                className="flex items-start justify-between gap-3 text-sm"
                              >
                                <span className="min-w-0 text-slate-600">
                                  {item.name || "Item"}
                                  {toNumber(item.amount) !== 0 && (
                                    <span className="ml-1 text-xs text-slate-400">
                                      ({item.amount} {item.unit || ""})
                                    </span>
                                  )}
                                </span>
                                <span className="shrink-0 font-medium text-slate-800">
                                  {formatCurrency(item.cost)}
                                </span>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </article>
                  );
                })}
              </div>
            )}
          </section>

          {/* Total stated by the supplier. */}
          <div
            className="flex items-center justify-between gap-4 rounded-xl p-4 text-white"
            style={{ backgroundColor: HEADER_COLOR }}
          >
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-white/70">
                Total da fatura
              </p>
              <p className="mt-1 text-sm text-white/85">
                Valor indicado pelo fornecedor
              </p>
            </div>
            <p className="shrink-0 text-xl font-bold">
              {formatCurrency(invoice.total_amount)}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
