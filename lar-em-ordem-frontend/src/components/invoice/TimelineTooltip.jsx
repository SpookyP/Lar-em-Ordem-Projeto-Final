import { monthLongLabel } from "./timelineShared";
import { formatMoney } from "../../utils/format";

export function TimelineTooltip({
  active,
  payload,
  color,
  benchmarkColor,
  serviceLabel,
}) {
  if (!active || !payload?.length) return null;

  const row = payload[0]?.payload;

  if (!row) return null;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="text-base font-semibold text-slate-800">
        {monthLongLabel(row.key)}
      </p>

      <p className="mt-2 flex items-center gap-2 text-base text-slate-700">
        <span
          className="h-2 w-2 rounded-full"
          style={{ backgroundColor: color }}
        />
        {serviceLabel}:{" "}
        <strong>
          {row.mine == null ? "sem dados" : formatMoney(row.mine)}
        </strong>
      </p>

      {row.benchmark != null && (
        <p className="mt-1 flex items-center gap-2 text-base text-slate-700">
          <span
            className="h-0.5 w-3"
            style={{ backgroundColor: benchmarkColor }}
          />
          Média similar: <strong>{formatMoney(row.benchmark)}</strong>
        </p>
      )}
    </div>
  );
}
