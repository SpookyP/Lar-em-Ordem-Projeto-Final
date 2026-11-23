import { SERVICE_TYPES, getInvoiceTypes } from "./invoiceShared";

export function InvoiceTypeBadge({ invoice }) {
  const types = getInvoiceTypes(invoice);

  if (types.length === 0) {
    return (
      <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
        Não identificado
      </span>
    );
  }

  return (
    <div className="flex min-w-0 flex-wrap gap-2">
      {types.map((id) => {
        const service = SERVICE_TYPES[id];
        const Icon = service.icon;

        return (
          <span
            key={id}
            className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold text-white"
            style={{
              backgroundColor: service.color,
              borderColor: service.color,
              color: "#FFFFFF",
            }}
          >
            <Icon size={14} />
            {service.name}
          </span>
        );
      })}
    </div>
  );
}
