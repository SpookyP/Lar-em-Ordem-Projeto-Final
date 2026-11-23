import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { TrendingDown, TrendingUp } from "lucide-react";
import { formatNumber, formatCompact } from "../../utils/format";
import { HEADER_COLOR, getServiceTheme } from "./benchmarkShared";
import { AnnualChartTooltip } from "./AnnualChartTooltip";

function getUserBarColor(service) {
  return getServiceTheme({ value: service }).color;
}

export function AnnualCostsChart({ rows, service }) {
  const chartRows = [...rows]
    .sort((a, b) => Number(a.year) - Number(b.year))
    .map((row) => ({
      ...row,
      user_total: Number(row.user_total) || 0,
      benchmark_total: Number(row.benchmark_total) || 0,
    }));
  if (chartRows.length === 0) return null;

  const userBarColor = getUserBarColor(service);
  const latest = chartRows[chartRows.length - 1];
  const previous =
    chartRows.length > 1 ? chartRows[chartRows.length - 2] : null;

  const trendPercentage =
    previous && Number(previous.user_total) > 0
      ? ((latest.user_total - previous.user_total) / previous.user_total) * 100
      : null;

  const partialLatest =
    Number(latest.estimated_months_count) > 0 ||
    latest.benchmark_estimated === true;

  const increased = trendPercentage !== null && trendPercentage > 0;
  const decreased = trendPercentage !== null && trendPercentage < 0;

  return (
    <section
      className="border-t-4 px-5 py-5 sm:px-6"
      style={{ borderTopColor: HEADER_COLOR }}
    >
      <div
        className="relative -mx-5 -mt-5 mb-4 flex flex-col gap-2 overflow-hidden px-5 py-4 sm:-mx-6 sm:flex-row sm:items-start sm:justify-between sm:px-6"
        style={{ backgroundColor: HEADER_COLOR }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />
        <div className="pointer-events-none absolute right-1/4 top-4 h-12 w-12 rounded-full border border-white/10" />

        <div className="relative z-10">
          <h3 className="text-base font-semibold text-white">
            Evolução anual dos custos
          </h3>
          <p className="mt-1 text-sm text-white/75">
            Gastos da habitação comparados com o benchmark médio.
          </p>
        </div>

        {trendPercentage !== null && (
          <div
            className={`relative z-10 inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-semibold ${
              increased
                ? "bg-red-50 text-red-700"
                : decreased
                  ? "bg-emerald-50 text-emerald-700"
                  : "bg-slate-100 text-slate-600"
            }`}
          >
            {increased ? (
              <TrendingUp size={17} aria-hidden="true" />
            ) : decreased ? (
              <TrendingDown size={17} aria-hidden="true" />
            ) : null}

            {increased
              ? `Aumento de ${formatNumber(trendPercentage, { decimals: 1 })}% face a ${previous.year}`
              : decreased
                ? `Redução de ${formatNumber(Math.abs(trendPercentage), { decimals: 1 })}% face a ${previous.year}`
                : `Sem alteração (0%) face a ${previous.year}`}

            {partialLatest && (
              <span className="font-normal opacity-80">· ano incompleto</span>
            )}
          </div>
        )}
      </div>

      <div className="h-65 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart
            data={chartRows}
            margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
            barGap={6}
            barCategoryGap="24%"
          >
            <CartesianGrid
              stroke="#e2e8f0"
              strokeDasharray="3 3"
              vertical={false}
            />
            <XAxis
              dataKey="year"
              tick={{ fill: "#64748b", fontSize: 14 }}
              tickLine={false}
              axisLine={{ stroke: "#e2e8f0" }}
            />
            <YAxis
              width={76}
              tickFormatter={(value) =>
                value >= 10000
                  ? `${formatCompact(value)} €`
                  : `${formatNumber(value, { decimals: 0 })} €`
              }
              tick={{ fill: "#64748b", fontSize: 12 }}
              tickLine={false}
              axisLine={false}
              allowDecimals={false}
            />
            <Tooltip
              content={<AnnualChartTooltip />}
              cursor={{ fill: "#f8fafc" }}
            />
            <Legend
              verticalAlign="bottom"
              height={36}
              iconType="circle"
              iconSize={9}
              formatter={(value) => (
                <span className="text-sm text-slate-600">{value}</span>
              )}
            />
            <Bar
              dataKey="user_total"
              name="Gasto da habitação"
              radius={[5, 5, 0, 0]}
              maxBarSize={42}
            >
              {chartRows.map((row) => (
                <Cell key={`user-${row.year}`} fill={userBarColor} />
              ))}
            </Bar>
            <Bar
              dataKey="benchmark_total"
              name="Benchmark médio"
              fill="#cbd5e1"
              radius={[5, 5, 0, 0]}
              maxBarSize={42}
            />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </section>
  );
}
