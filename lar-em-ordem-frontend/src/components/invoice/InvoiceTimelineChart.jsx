import { useEffect, useMemo, useRef, useState } from "react";
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  ReferenceArea,
  ReferenceLine,
  Tooltip,
} from "recharts";
import {
  buildMonthlySeries,
  monthKey,
  keyToIndex,
  indexToKey,
  monthsBetween,
  asNumber,
} from "../../utils/invoiceTimelineUtils";

const MONTHS = [
  "Janeiro",
  "Fevereiro",
  "Março",
  "Abril",
  "Maio",
  "Junho",
  "Julho",
  "Agosto",
  "Setembro",
  "Outubro",
  "Novembro",
  "Dezembro",
];

const MONTHS_SHORT = [
  "Jan",
  "Fev",
  "Mar",
  "Abr",
  "Mai",
  "Jun",
  "Jul",
  "Ago",
  "Set",
  "Out",
  "Nov",
  "Dez",
];

const MIN_MONTH_WIDTH = 96;
const Y_AXIS_WIDTH = 56;
const CHART_HEIGHT = 240;
const MAX_MONTH_DISTANCE = 24;
const HEADER_COLOR = "#1C3557";

const SERVICE_STYLE = {
  all: {
    label: "Total",
    color: "#583D69",
    benchmarkColor: "#450e67",
  },
  1: {
    label: "Eletricidade",
    color: "#142943",
    benchmarkColor: "#0a1726",
  },
  2: {
    label: "Água",
    color: "#0F766E",
    benchmarkColor: "#08443f",
  },
  3: {
    label: "Gás",
    color: "#EA580C",
    benchmarkColor: "#923806",
  },
};

const RANGE_COLOR = "#dc2626";

const PRESETS = [
  { label: "Último mês", months: 1 },
  { label: "Últimos 3 meses", months: 3 },
  { label: "Últimos 6 meses", months: 6 },
  { label: "Últimos 12 meses", months: 12 },
];

const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

const percent = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 1,
});

const monthLabel = (key) => MONTHS_SHORT[keyToIndex(key) % 12];

const monthLongLabel = (key) =>
  `${MONTHS[keyToIndex(key) % 12]} ${key.slice(0, 4)}`;

function niceScale(maxValue) {
  if (!Number.isFinite(maxValue) || maxValue <= 0) {
    return { max: 10, ticks: [0, 5, 10] };
  }

  const steps = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2500, 5000];

  const step = steps.find((s) => maxValue / s <= 5) ?? 10000;
  const max = Math.ceil(maxValue / step) * step;
  const ticks = [];

  for (let value = 0; value <= max; value += step) {
    ticks.push(value);
  }

  return { max, ticks };
}

function presetRange(monthKeys, months) {
  if (monthKeys.length === 0) return null;

  const last = monthKeys[monthKeys.length - 1];

  const startIndex = Math.max(
    keyToIndex(monthKeys[0]),
    keyToIndex(last) - months + 1,
  );

  return {
    start: indexToKey(startIndex),
    end: last,
  };
}

function TimelineTooltip({
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
          {row.mine == null ? "sem dados" : money.format(row.mine)}
        </strong>
      </p>

      {row.benchmark != null && (
        <p className="mt-1 flex items-center gap-2 text-base text-slate-700">
          <span
            className="h-0.5 w-3"
            style={{ backgroundColor: benchmarkColor }}
          />
          Média similar: <strong>{money.format(row.benchmark)}</strong>
        </p>
      )}
    </div>
  );
}

function DateRangeSelects({ range, currentMonthIndex, onChange }) {
  const [draft, setDraft] = useState(range);
  const [error, setError] = useState("");

  const currentYear = Math.floor(currentMonthIndex / 12);
  const currentMonth = currentMonthIndex % 12;

  const years = Array.from(
    { length: currentYear - 1899 },
    (_, index) => currentYear - index,
  );

  function updateDate(field, part, value) {
    const [year, month] = draft[field].split("-").map(Number);

    let nextYear = year;
    let nextMonth = month - 1;

    if (part === "year") {
      nextYear = Number(value);

      if (nextYear === currentYear && nextMonth > currentMonth) {
        nextMonth = currentMonth;
      }
    } else {
      nextMonth = Number(value);
    }

    const next = {
      ...draft,
      [field]: monthKey(nextYear, nextMonth),
    };

    setDraft(next);

    const startIndex = keyToIndex(next.start);
    const endIndex = keyToIndex(next.end);

    if (startIndex > currentMonthIndex || endIndex > currentMonthIndex) {
      setError("Não podes selecionar uma data futura.");
      return;
    }

    if (endIndex < startIndex) {
      setError("A data final não pode ser anterior à data inicial.");
      return;
    }

    const distance = Math.abs(endIndex - startIndex);

    if (distance > MAX_MONTH_DISTANCE) {
      setError(
        "O intervalo máximo é de 25 meses, contando com o mês inicial e o mês final.",
      );
      return;
    }

    setError("");
    onChange(next);
  }

  function renderDateSelector(field, label, tone) {
    const [year, month] = draft[field].split("-").map(Number);

    const availableMonths = MONTHS.map((name, index) => ({
      value: index,
      label: name,
    })).filter(({ value }) => year < currentYear || value <= currentMonth);

    const palette =
      tone === "indigo"
        ? {
            icon: "bg-indigo-100 text-indigo-700",
            dot: "bg-indigo-700",
            focus: "focus:border-indigo-400 focus:ring-indigo-100",
          }
        : {
            icon: "bg-teal-100 text-teal-700",
            dot: "bg-teal-500",
            focus: "focus:border-teal-400 focus:ring-teal-100",
          };

    return (
      <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:shadow-md sm:p-4">
        <div className="mb-3 flex items-center gap-2.5">
          <span
            className={`flex h-9 w-9 items-center justify-center rounded-xl ${palette.icon}`}
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-4.5[18px]"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
              aria-hidden="true"
            >
              <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
              <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" />
              <path d="M8 13h.01M12 13h.01M16 13h.01M8 16.5h.01M12 16.5h.01" />
            </svg>
          </span>

          <div className="min-w-0">
            <label className="block text-base font-semibold text-slate-800">
              {label}
            </label>

            <span className="mt-0.5 flex items-center gap-1.5 text-[13px] text-slate-400">
              <span className={`h-1.5 w-1.5 rounded-full ${palette.dot}`} />
              {monthLongLabel(draft[field])}
            </span>
          </div>
        </div>

        <div className="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
          <div>
            <span className="mb-1.5 block text-xs font-bold uppercase tracking-[0.08em] text-slate-400">
              Mês
            </span>

            <select
              value={month - 1}
              onChange={(event) =>
                updateDate(field, "month", event.target.value)
              }
              aria-label={`${label}: mês`}
              className={`h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base font-medium text-slate-700 outline-none transition hover:border-slate-300 hover:bg-white focus:bg-white focus:ring-4 ${palette.focus}`}
            >
              {availableMonths.map(({ value, label: monthName }) => (
                <option key={value} value={value}>
                  {monthName}
                </option>
              ))}
            </select>
          </div>

          <div>
            <span className="mb-1.5 block text-xs font-bold uppercase tracking-[0.08em] text-slate-400">
              Ano
            </span>

            <select
              value={year}
              onChange={(event) =>
                updateDate(field, "year", event.target.value)
              }
              aria-label={`${label}: ano`}
              className={`h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base font-medium text-slate-700 outline-none transition hover:border-slate-300 hover:bg-white focus:bg-white focus:ring-4 ${palette.focus}`}
            >
              {years.map((yearOption) => (
                <option key={yearOption} value={yearOption}>
                  {yearOption}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {renderDateSelector("start", "Data inicial", "indigo")}
        {renderDateSelector("end", "Data final", "teal")}
      </div>

      {error && (
        <div
          role="alert"
          className="mt-3 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm leading-relaxed text-rose-700"
        >
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-rose-100">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-4 w-4"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              aria-hidden="true"
            >
              <path d="M12 8v5M12 16.5h.01" />
              <circle cx="12" cy="12" r="9" />
            </svg>
          </span>

          <span>
            <strong className="font-semibold">Intervalo inválido.</strong>{" "}
            {error}
          </span>
        </div>
      )}
    </div>
  );
}

export default function InvoiceTimelineChart({
  invoices = [],
  range: rangeProp = null,
  onRangeChange,
  serviceFilter = "all",
  benchmark = null,
}) {
  const style = SERVICE_STYLE[serviceFilter] ?? SERVICE_STYLE.all;

  const [innerRange, setInnerRange] = useState(null);
  const [advancedEnabled, setAdvancedEnabled] = useState(false);

  const wrapRef = useRef(null);
  const [wrapWidth, setWrapWidth] = useState(0);

  const scrolledToEnd = useRef(false);
  const emittedDefault = useRef(false);

  const now = new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth();
  const currentMonthIndex = currentYear * 12 + currentMonth;
  const previousMonthIndex = currentMonthIndex - 1;

  const mine = useMemo(
    () => buildMonthlySeries(invoices, serviceFilter),
    [invoices, serviceFilter],
  );

  const benchmarkMap = useMemo(() => {
    const grouped = new Map();

    (Array.isArray(benchmark) ? benchmark : []).forEach((entry) => {
      if (!entry?.period_start || entry.average_cost == null) {
        return;
      }

      const key = String(entry.period_start).slice(0, 7);
      const typeId = Number(entry.consumption_type_id);

      if (!/^\d{4}-\d{2}$/.test(key)) return;
      if (![1, 2, 3].includes(typeId)) return;

      if (!grouped.has(key)) {
        grouped.set(key, new Map());
      }

      grouped.get(key).set(typeId, asNumber(entry.average_cost));
    });

    const result = new Map();

    grouped.forEach((services, key) => {
      if (!mine.has(key)) return;

      if (serviceFilter === "all") {
        if ([1, 2, 3].every((id) => services.has(id))) {
          result.set(key, services.get(1) + services.get(2) + services.get(3));
        }

        return;
      }

      const selectedType = Number(serviceFilter);

      if (services.has(selectedType)) {
        result.set(key, services.get(selectedType));
      }
    });

    return result;
  }, [benchmark, serviceFilter, mine]);

  const latestDataIndex = useMemo(() => {
    const indexes = [...mine.keys()].map(keyToIndex);

    return indexes.length ? Math.max(...indexes) : null;
  }, [mine]);

  const initialAdvancedRange = useMemo(
    () => ({
      start: indexToKey(previousMonthIndex - MAX_MONTH_DISTANCE),
      end: indexToKey(previousMonthIndex),
    }),
    [previousMonthIndex],
  );

  const selectedBaseRange = rangeProp ?? innerRange;

  const range = useMemo(() => {
    if (!selectedBaseRange?.start || !selectedBaseRange?.end) {
      if (advancedEnabled) return initialAdvancedRange;
      if (latestDataIndex == null) return null;

      const keys = monthsBetween(
        indexToKey(latestDataIndex - 23),
        indexToKey(latestDataIndex),
      );

      return presetRange(keys, 1);
    }

    return {
      start: selectedBaseRange.start,
      end: selectedBaseRange.end,
    };
  }, [
    selectedBaseRange,
    advancedEnabled,
    initialAdvancedRange,
    latestDataIndex,
  ]);

  const chartRange = useMemo(() => {
    if (!range) return null;

    const startIndex = keyToIndex(range.start);
    const endIndex = keyToIndex(range.end);

    return {
      start: indexToKey(Math.min(startIndex, endIndex)),
      end: indexToKey(Math.max(startIndex, endIndex)),
    };
  }, [range]);

  const monthKeys = useMemo(() => {
    if (latestDataIndex == null && !advancedEnabled && !range) {
      return [];
    }

    const anchor = range
      ? Math.max(keyToIndex(range.start), keyToIndex(range.end))
      : latestDataIndex;

    const end = Math.min(anchor, currentMonthIndex);

    return monthsBetween(indexToKey(end - MAX_MONTH_DISTANCE), indexToKey(end));
  }, [latestDataIndex, advancedEnabled, range, currentMonthIndex]);

  const yearGroups = useMemo(() => {
    const groups = [];

    monthKeys.forEach((key) => {
      const year = key.slice(0, 4);
      const lastGroup = groups[groups.length - 1];

      if (lastGroup?.year === year) {
        lastGroup.count += 1;
      } else {
        groups.push({ year, count: 1 });
      }
    });

    return groups;
  }, [monthKeys]);

  const data = useMemo(
    () =>
      monthKeys.map((key) => ({
        key,
        mine: mine.has(key) ? Number(mine.get(key).toFixed(2)) : null,
        benchmark: benchmarkMap.has(key)
          ? Number(benchmarkMap.get(key).toFixed(2))
          : null,
      })),
    [monthKeys, mine, benchmarkMap],
  );

  const scale = useMemo(
    () =>
      niceScale(
        Math.max(
          0,
          ...data.map((row) => row.mine ?? 0),
          ...data.map((row) => row.benchmark ?? 0),
        ),
      ),
    [data],
  );

  const comparison = useMemo(() => {
    if (!chartRange || benchmarkMap.size === 0) return null;

    const startIndex = keyToIndex(chartRange.start);
    const endIndex = keyToIndex(chartRange.end);

    let mineTotal = 0;
    let benchmarkTotal = 0;
    let comparableMonths = 0;

    data.forEach((row) => {
      const index = keyToIndex(row.key);

      if (
        index >= startIndex &&
        index <= endIndex &&
        row.mine != null &&
        row.benchmark != null
      ) {
        mineTotal += row.mine;
        benchmarkTotal += row.benchmark;
        comparableMonths += 1;
      }
    });

    if (comparableMonths === 0) return null;

    const difference = mineTotal - benchmarkTotal;

    const percentageDifference =
      benchmarkTotal > 0 ? (difference / benchmarkTotal) * 100 : null;

    return {
      mineTotal,
      benchmarkTotal,
      difference,
      percentageDifference,
      comparableMonths,
    };
  }, [chartRange, data, benchmarkMap]);

  useEffect(() => {
    const element = wrapRef.current;

    if (!element) return undefined;

    const observer = new ResizeObserver(([entry]) => {
      setWrapWidth(entry.contentRect.width);
    });

    observer.observe(element);

    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    const element = wrapRef.current;

    if (element && monthKeys.length > 0 && !scrolledToEnd.current) {
      scrolledToEnd.current = true;
      element.scrollLeft = element.scrollWidth;
    }
  }, [monthKeys.length, wrapWidth]);

  useEffect(() => {
    if (range && !rangeProp && !emittedDefault.current) {
      emittedDefault.current = true;
      onRangeChange?.(range);
    }
  }, [range, rangeProp, onRangeChange]);

  function updateRange(next) {
    if (!next) return;

    const startIndex = keyToIndex(next.start);
    const endIndex = keyToIndex(next.end);

    if (
      endIndex < startIndex ||
      Math.abs(endIndex - startIndex) > MAX_MONTH_DISTANCE ||
      startIndex > currentMonthIndex ||
      endIndex > currentMonthIndex
    ) {
      return;
    }

    setInnerRange(next);
    onRangeChange?.(next);
  }

  const monthCount = monthKeys.length;

  const monthWidth = Math.max(
    MIN_MONTH_WIDTH,
    wrapWidth > 0 ? (wrapWidth - Y_AXIS_WIDTH) / Math.max(monthCount, 1) : 0,
  );

  const chartWidth = Y_AXIS_WIDTH + monthWidth * monthCount;

  if (mine.size === 0 || monthCount === 0 || !range || !chartRange) {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-6 text-center">
        <p className="text-lg font-medium text-slate-700">
          Ainda não existem dados para a evolução.
        </p>

        <p className="mt-1 text-base text-slate-500">
          Guarda uma fatura com período de faturação para ver a linha temporal.
        </p>
      </section>
    );
  }

  const chartStartIndex = keyToIndex(chartRange.start);
  const chartEndIndex = keyToIndex(chartRange.end);

  const isInside = (key) => {
    const index = keyToIndex(key);
    return index >= chartStartIndex && index <= chartEndIndex;
  };

  const activePreset = PRESETS.find((preset) => {
    const target = presetRange(monthKeys, preset.months);

    return target?.start === range.start && target?.end === range.end;
  });

  const renderDot = ({ cx, cy, payload }) => {
    if (cx == null || cy == null || payload?.mine == null) {
      return <g key={`empty-${payload?.key}`} />;
    }

    return (
      <circle
        key={`dot-${payload.key}`}
        cx={cx}
        cy={cy}
        r={3.5}
        fill={isInside(payload.key) ? style.color : "#ffffff"}
        stroke={style.color}
        strokeWidth={2}
      />
    );
  };

  const renderBenchmarkDot = ({ cx, cy, payload }) => {
    if (cx == null || cy == null || payload?.benchmark == null) {
      return <g key={`benchmark-empty-${payload?.key}`} />;
    }

    return (
      <rect
        key={`benchmark-dot-${payload.key}`}
        x={cx - 3.5}
        y={cy - 3.5}
        width={7}
        height={7}
        rx={1}
        fill={style.benchmarkColor}
        stroke="#ffffff"
        strokeWidth={1.5}
      />
    );
  };

  return (
    <section className="w-full min-w-0 max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      {/* Cabeçalho principal com o serviço e a comparação com a média. */}
      <div
        className="relative -mx-4 -mt-4 mb-4 flex flex-col gap-3 overflow-hidden rounded-t-2xl p-5 text-white shadow-sm sm:-mx-6 sm:-mt-6 sm:flex-row sm:items-center sm:justify-between sm:p-6"
        style={{ backgroundColor: HEADER_COLOR }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />

        <div className="relative flex flex-wrap items-center gap-3">
          <div>
            <h2 className="text-lg font-bold text-white">
              {benchmarkMap.size > 0
                ? "Evolução vs. habitações similares"
                : "Evolução mensal"}
            </h2>

            <p className="mt-1 text-sm text-white/75">
              Acompanha a evolução dos custos ao longo do tempo.
            </p>
          </div>

          {/* Indicador da diferença entre a habitação e a média. */}
          {comparison != null && (
            <span
              className={`rounded-full px-2.5 py-1 text-sm font-semibold ${
                comparison.difference <= 0
                  ? "bg-emerald-100 text-emerald-800"
                  : "bg-red-100 text-red-800"
              }`}
            >
              {comparison.percentageDifference == null
                ? "Sem média para comparar"
                : comparison.percentageDifference === 0
                  ? "Em linha com a média"
                  : comparison.percentageDifference < 0
                    ? `▼ ${percent.format(
                        Math.abs(comparison.percentageDifference),
                      )}% abaixo da média`
                    : `▲ ${percent.format(
                        comparison.percentageDifference,
                      )}% acima da média`}
            </span>
          )}
        </div>

        {/* Legenda das linhas apresentadas no gráfico. */}
        <div className="relative flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/90">
          <span className="inline-flex items-center rounded-full border border-white/25 bg-white/10 px-2.5 py-1 font-semibold text-white">
            {style.label}
          </span>

          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <span className="h-0.75w-6 rounded-full bg-white" />A minha casa
          </span>

          {benchmarkMap.size > 0 && (
            <span className="inline-flex items-center gap-2 whitespace-nowrap">
              <span className="w-6 border-t-2 border-dashed border-white/80" />
              Média similar
            </span>
          )}
        </div>
      </div>

      {/* Intervalo ativo e opções de seleção rápida. */}
      <div className="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <p className="text-base text-slate-500">
          <span
            className="mr-2 border-l-2 pl-2"
            style={{ borderColor: "#1C3557" }}
          >
            Intervalo ativo
          </span>

          <strong className="text-slate-900">
            {monthLongLabel(range.start)} → {monthLongLabel(range.end)}
          </strong>
        </p>

        {!advancedEnabled && (
          <div className="flex flex-wrap gap-2">
            {PRESETS.map((preset) => (
              <button
                key={preset.label}
                type="button"
                onClick={() =>
                  updateRange(presetRange(monthKeys, preset.months))
                }
                aria-pressed={activePreset?.label === preset.label}
                className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                  activePreset?.label === preset.label
                    ? "border-navy bg-navy text-white"
                    : "border-slate-200 text-slate-600 hover:border-navy/40 hover:bg-navy/5"
                }`}
              >
                {preset.label}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Gráfico com deslocamento horizontal para navegar pelos meses. */}
      <div
        ref={wrapRef}
        className="mt-4 w-full min-w-0 max-w-full overflow-x-auto rounded-xl border border-slate-200"
        style={{ contain: "inline-size" }}
      >
        <div style={{ width: chartWidth, minWidth: chartWidth }}>
          {/* Cabeçalho dos anos alinhado com os meses do gráfico. */}
          <div
            className="flex h-7 items-center border-b border-slate-100"
            style={{ width: chartWidth }}
          >
            <div style={{ width: Y_AXIS_WIDTH, flexShrink: 0 }} />

            {yearGroups.map((group, index) => (
              <div
                key={group.year}
                className={`flex h-full items-center justify-center text-sm font-bold text-slate-500 ${
                  index > 0 ? "border-l border-slate-300" : ""
                }`}
                style={{
                  width: monthWidth * group.count,
                  flexShrink: 0,
                }}
              >
                {group.year}
              </div>
            ))}
          </div>

          {/* Linhas dos consumos e dos benchmarks. */}
          <LineChart
            width={chartWidth}
            height={CHART_HEIGHT}
            data={data}
            margin={{ top: 16, right: 0, bottom: 0, left: 0 }}
          >
            <CartesianGrid
              vertical={false}
              stroke="#e2e8f0"
              strokeDasharray="3 3"
            />

            <XAxis
              dataKey="key"
              interval={0}
              tickFormatter={monthLabel}
              padding={{
                left: monthWidth / 2,
                right: monthWidth / 2,
              }}
              tickLine={false}
              axisLine={false}
              tick={{ fontSize: 14, fill: "#94a3b8" }}
            />

            <YAxis
              width={Y_AXIS_WIDTH}
              domain={[0, scale.max]}
              ticks={scale.ticks}
              tickFormatter={(value) => `€${value}`}
              tickLine={false}
              axisLine={false}
              tick={{ fontSize: 13, fill: "#94a3b8" }}
            />

            {/* Destaca visualmente o intervalo temporal selecionado. */}
            <ReferenceArea
              x1={chartRange.start}
              x2={chartRange.end}
              fill={RANGE_COLOR}
              fillOpacity={0.08}
              stroke="none"
            />

            <ReferenceLine
              x={chartRange.start}
              stroke={RANGE_COLOR}
              strokeWidth={2}
            />

            <ReferenceLine
              x={chartRange.end}
              stroke={RANGE_COLOR}
              strokeWidth={2}
            />

            {/* Informação detalhada ao passar o rato sobre os pontos. */}
            <Tooltip
              content={
                <TimelineTooltip
                  color={style.color}
                  benchmarkColor={style.benchmarkColor}
                  serviceLabel={style.label}
                />
              }
            />

            {/* Linha de benchmark, apresentada quando existem valores disponíveis. */}
            {benchmarkMap.size > 0 && (
              <>
                <Line
                  dataKey="benchmark"
                  type="monotone"
                  stroke="#ffffff"
                  strokeWidth={7}
                  strokeLinecap="round"
                  dot={false}
                  activeDot={false}
                  connectNulls={false}
                  isAnimationActive={false}
                />

                <Line
                  dataKey="benchmark"
                  type="monotone"
                  stroke={style.benchmarkColor}
                  strokeWidth={2.5}
                  strokeDasharray="7 6"
                  strokeLinecap="round"
                  dot={renderBenchmarkDot}
                  activeDot={{
                    r: 5,
                    fill: style.benchmarkColor,
                    stroke: "#ffffff",
                    strokeWidth: 2,
                  }}
                  connectNulls={false}
                  isAnimationActive={false}
                />
              </>
            )}

            {/* Linha dos custos efetivos da habitação. */}
            <Line
              dataKey="mine"
              type="monotone"
              stroke="#ffffff"
              strokeWidth={8}
              strokeLinecap="round"
              dot={false}
              activeDot={false}
              isAnimationActive={false}
            />

            <Line
              dataKey="mine"
              type="monotone"
              stroke={style.color}
              strokeWidth={2}
              strokeLinecap="round"
              dot={renderDot}
              activeDot={{
                r: 6,
                fill: style.color,
                stroke: "#ffffff",
                strokeWidth: 2.5,
              }}
              isAnimationActive={false}
            />
          </LineChart>
        </div>
      </div>

      {/* Configuração do filtro avançado de período. */}
      <div className="mt-5 border-t border-slate-100 pt-5">
        <label
          className={`group flex cursor-pointer items-center gap-3.5 rounded-2xl border p-3.5 transition-all duration-200 sm:p-4 ${
            advancedEnabled
              ? "border-navy/20 bg-linear-to-r from-[#1C3557]/[0.07] via-white to-[#1C3557]/4 shadow-sm shadow-[#1C3557]/10"
              : "border-slate-200 bg-white hover:border-navy/30 hover:bg-navy/2.5"
          }`}
        >
          <span
            className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition ${
              advancedEnabled
                ? "bg-navy text-white shadow-md shadow-[#1C3557]/20"
                : "bg-slate-100 text-slate-500 group-hover:bg-navy/10 group-hover:text-navy"
            }`}
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-5 w-5"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
              aria-hidden="true"
            >
              <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
              <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h3M8 16.5h7" />
            </svg>
          </span>

          {/* Ativa ou desativa a seleção personalizada de datas. */}
          <input
            type="checkbox"
            checked={advancedEnabled}
            onChange={(event) => {
              const enabled = event.target.checked;

              setAdvancedEnabled(enabled);

              if (enabled) {
                updateRange(initialAdvancedRange);
              } else {
                const defaultEndIndex = Math.min(
                  latestDataIndex ?? currentMonthIndex,
                  currentMonthIndex,
                );

                const defaultMonthKeys = monthsBetween(
                  indexToKey(defaultEndIndex - MAX_MONTH_DISTANCE),
                  indexToKey(defaultEndIndex),
                );

                updateRange(presetRange(defaultMonthKeys, 1));
              }
            }}
            className="h-4 w-4 shrink-0 rounded border-slate-300 text-navy focus:ring-[#1C3557]"
          />

          <span className="min-w-0 flex-1">
            <span className="flex flex-wrap items-center gap-2">
              <span className="text-base font-bold text-slate-800">
                Filtro avançado de período
              </span>

              <span
                className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase tracking-wide ${
                  advancedEnabled
                    ? "bg-navy/10 text-navy"
                    : "bg-slate-100 text-slate-500"
                }`}
              >
                {advancedEnabled ? "Ativo" : "Desativado"}
              </span>
            </span>

            <span className="mt-1 block text-sm leading-relaxed text-slate-500">
              Define um intervalo personalizado para explorar a evolução dos
              teus consumos.
            </span>
          </span>

          <svg
            viewBox="0 0 24 24"
            fill="none"
            className={`h-5 w-5 shrink-0 text-slate-400 transition-transform ${
              advancedEnabled ? "rotate-180 text-navy" : ""
            }`}
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            aria-hidden="true"
          >
            <path d="m6 9 6 6 6-6" />
          </svg>
        </label>

        {/* Seletores de datas apresentados quando o filtro avançado está ativo. */}
        {advancedEnabled && (
          <div className="relative mt-3 overflow-hidden rounded-2xl border border-navy/15 bg-linear-to-br from-[#1C3557]/5.5 via-white to-[#1C3557]/[0.035] p-4 shadow-sm sm:p-5">
            <div className="pointer-events-none absolute -right-10 -top-12 h-36 w-36 rounded-full bg-navy/6 blur-2xl" />
            <div className="pointer-events-none absolute -bottom-16 left-1/3 h-36 w-36 rounded-full bg-navy/4.5 blur-2xl" />

            <div className="relative">
              <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2.5">
                  <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      className="h-4.5 w-4.5"
                      stroke="currentColor"
                      strokeWidth="1.8"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      aria-hidden="true"
                    >
                      <path d="M4 19V5M4 19h16" />
                      <path d="m7 15 3-4 3 2 5-7" />
                    </svg>
                  </span>

                  <div>
                    <p className="text-base font-bold text-slate-800">
                      Período personalizado
                    </p>

                    <p className="mt-0.5 text-sm text-slate-500">
                      Escolhe as datas que queres comparar.
                    </p>
                  </div>
                </div>

                <span className="inline-flex items-center gap-1.5 rounded-full border border-navy/15 bg-white/90 px-3 py-1.5 text-[13px] font-bold text-navy shadow-sm">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    className="h-3.5 w-3.5"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                  </svg>
                  Até 25 meses
                </span>
              </div>

              <DateRangeSelects
                key={`${range.start}-${range.end}`}
                range={range}
                currentMonthIndex={currentMonthIndex}
                onChange={updateRange}
              />

              {/* Informação sobre os limites de seleção do período. */}
              <div className="mt-3 flex items-start gap-2.5 rounded-xl border border-white/90 bg-white/75 px-3.5 py-3 shadow-sm">
                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-navy/10 text-navy">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    className="h-4 w-4"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <path d="m5 12 4 4L19 6" />
                  </svg>
                </span>

                <p className="text-[13px] leading-relaxed text-slate-600">
                  Podes selecionar qualquer mês e ano desde 1900 até ao mês
                  atual. O intervalo máximo é de{" "}
                  <strong className="font-semibold text-slate-800">
                    25 meses de calendário, contando o mês inicial e o final
                  </strong>
                  . O gráfico apresenta sempre os meses por ordem cronológica.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
