import { formatMoney } from "../../utils/format";

export function AnnualChartTooltip({ active, payload, label }) {
  if (!active || !payload?.length) return null;

  const row = payload[0]?.payload;

  if (!row) return null;

  const estimated =
    Number(row.estimated_months_count) > 0 ||
    row.benchmark_estimated === true ||
    (row.estimated_services ?? []).length > 0;

  return (
    <div className="min-w-47.5 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="mb-2 text-base font-semibold text-slate-900">{label}</p>

      <div className="space-y-1.5 text-sm">
        <div className="flex items-center justify-between gap-4">
          <span className="text-slate-500">Gasto da habitação</span>
          <strong className="tabular-nums text-slate-800">
            {formatMoney(row.user_total)}
          </strong>
        </div>

        <div className="flex items-center justify-between gap-4">
          <span className="text-slate-500">Benchmark médio</span>
          <strong className="tabular-nums text-slate-800">
            {formatMoney(row.benchmark_total)}
          </strong>
        </div>
      </div>

      {estimated && (
        <p className="mt-2 border-t border-slate-100 pt-2 text-xs text-amber-700">
          Inclui valores estimados.
        </p>
      )}
    </div>
  );
}
