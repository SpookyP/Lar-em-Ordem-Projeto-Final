import { formatMoney } from "../../utils/format";

export function ChartTooltip({ active, payload }) {
  if (!active || !payload?.length) return null;

  const item = payload[0]?.payload;
  if (!item) return null;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
      <p className="text-base font-semibold text-slate-800">{item.name}</p>

      <p className="mt-1 text-sm text-slate-500">{item.utility}</p>

      {item.description && (
        <p className="mt-2 max-w-64 text-sm text-slate-500">
          {item.description}
        </p>
      )}

      <p
        className={`mt-2 text-base font-semibold ${
          item.isCredit ? "text-emerald-700" : "text-slate-900"
        }`}
      >
        {formatMoney(item.cost)}
        {item.isCredit ? " · Crédito" : ""}
      </p>

      {item.invoiceCount > 0 && (
        <p className="mt-1 max-w-64 text-sm text-slate-400">
          Incluído em {item.invoiceCount}{" "}
          {item.invoiceCount === 1 ? "fatura" : "faturas"}
        </p>
      )}
    </div>
  );
}
