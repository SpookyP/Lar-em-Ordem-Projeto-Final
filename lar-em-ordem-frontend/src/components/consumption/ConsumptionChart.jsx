import { useMemo, useState } from "react";
import { PieChart, Pie, ResponsiveContainer, Sector } from "recharts";
import { ChevronRight } from "lucide-react";
import { formatMoney } from "../../utils/format";

const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

const EMPTY_TOTALS = { 1: 0, 2: 0, 3: 0 };

const ITEM_KEYS = [
  "items",
  "consumption_items",
  "consumptionItems",
  "details",
  "lines",
];

function asNumber(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value).trim().replace(/\s/g, "").replace(/[€]/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);

  return Number.isFinite(result) ? result : 0;
}

function getConsumptions(invoice) {
  const list = invoice?.consumptions ?? invoice?.utilities ?? [];

  return Array.isArray(list) ? list : [];
}

function getConsumptionItems(consumption) {
  const possibleItems = ITEM_KEYS.map((key) => consumption?.[key]);

  return (
    possibleItems.find((items) => Array.isArray(items) && items.length > 0) ??
    possibleItems.find(Array.isArray) ??
    []
  );
}

function getTypeId(consumption) {
  const raw =
    consumption?.consumption_type_id ??
    consumption?.consumptionTypeId ??
    consumption?.consumption_type?.id ??
    consumption?.consumptionType?.id ??
    consumption?.type_id ??
    consumption?.type?.id;

  const id = Number(raw);

  if (UTILITIES[id]) return id;

  const name = String(
    consumption?.consumption_type?.name ??
      consumption?.consumptionType?.name ??
      consumption?.type?.name ??
      consumption?.name ??
      "",
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;

  if (
    name.includes("água") ||
    name.includes("agua") ||
    name.includes("water")
  ) {
    return 2;
  }

  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

function getItemCost(item) {
  return asNumber(
    item?.cost ??
      item?.total_cost ??
      item?.totalCost ??
      item?.amount_cost ??
      item?.price ??
      0,
  );
}

function getConsumptionCost(consumption) {
  if (consumption?.cost != null) {
    return asNumber(consumption.cost);
  }

  if (consumption?.total_cost != null) {
    return asNumber(consumption.total_cost);
  }

  if (consumption?.totalCost != null) {
    return asNumber(consumption.totalCost);
  }

  const items = getConsumptionItems(consumption);

  if (items.length > 0) {
    return items.reduce((sum, item) => sum + getItemCost(item), 0);
  }

  return 0;
}

function normalizeInvoices(invoices) {
  const totals = { ...EMPTY_TOTALS };
  const invoiceList = Array.isArray(invoices) ? invoices : [];

  invoiceList.forEach((invoice) => {
    const consumptions = getConsumptions(invoice);

    consumptions.forEach((consumption) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      totals[typeId] += getConsumptionCost(consumption);
    });
  });

  return Object.entries(UTILITIES)
    .map(([id, utility]) => ({
      id: Number(id),
      name: utility.name,
      color: utility.color,
      value: Number(totals[id].toFixed(2)),
    }))
    .filter((item) => item.value !== 0);
}

function parseDate(value) {
  if (!value) return null;

  if (typeof value === "string") {
    const match = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);

    if (match) {
      const [, year, month, day] = match;

      const date = new Date(Number(year), Number(month) - 1, Number(day));

      return Number.isNaN(date.getTime()) ? null : date;
    }
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? null : date;
}

function getInvoiceDate(invoice) {
  return (
    invoice?.period_start ??
    invoice?.period_end ??
    invoice?.issue_date ??
    invoice?.invoice_date ??
    invoice?.date ??
    invoice?.created_at ??
    null
  );
}

function getPeriodStart(period) {
  const now = new Date();
  const months = Number(period);

  const validMonths = Number.isFinite(months) && months > 0 ? months : 1;

  return new Date(now.getFullYear(), now.getMonth() - validMonths, 1);
}

export default function ConsumptionChart({
  invoices = [],
  loading = false,
  error = "",
  onCategoryClick,
  period = 1,
}) {
  const [focusedId, setFocusedId] = useState(null);

  const filteredInvoices = useMemo(() => {
    const startDate = getPeriodStart(period);
    const endDate = new Date();

    const invoiceList = Array.isArray(invoices) ? invoices : [];

    return invoiceList.filter((invoice) => {
      const date = parseDate(getInvoiceDate(invoice));

      return date && date >= startDate && date <= endDate;
    });
  }, [invoices, period]);

  const data = useMemo(
    () => normalizeInvoices(filteredInvoices),
    [filteredInvoices],
  );

  const total = useMemo(
    () => data.reduce((sum, item) => sum + item.value, 0),
    [data],
  );

  const focusedItem = data.find((item) => item.id === focusedId) ?? null;

  const center = focusedItem
    ? {
        label: focusedItem.name,
        value: focusedItem.value,
      }
    : {
        label: "Total",
        value: total,
      };

  const biggest = data.reduce(
    (max, item) => (!max || item.value > max.value ? item : max),
    null,
  );

  if (loading) {
    return (
      <div className="flex min-h-40 items-center justify-center">
        <p className="text-sm text-slate-500">A carregar consumos...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
        {error}
      </div>
    );
  }

  if (data.length === 0 || total === 0) {
    return (
      <div className="py-10 text-center">
        <p className="font-medium text-slate-700">
          Ainda não existem consumos para apresentar.
        </p>

        <p className="mt-1 text-sm text-slate-500">
          Adiciona uma fatura para consultar a distribuição dos custos.
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center gap-8 @xl:flex-row @xl:gap-8">
      {/* Pie Chart with total or category selected. */}
      <div className="relative shrink-0" style={{ width: 260, height: 260 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart accessibilityLayer={false}>
            <Pie
              data={data}
              dataKey="value"
              nameKey="name"
              cx="50%"
              cy="50%"
              innerRadius={82}
              outerRadius={120}
              startAngle={90}
              endAngle={-270}
              paddingAngle={2}
              stroke="none"
              isAnimationActive={false}
              onMouseEnter={(_, index) => setFocusedId(data[index]?.id ?? null)}
              onMouseLeave={() => setFocusedId(null)}
              shape={(props) => {
                const { index } = props;
                const item = data[index];
                const isFocused = focusedItem?.id === item?.id;

                return (
                  <Sector
                    {...props}
                    fill={item?.color}
                    opacity={!focusedItem || isFocused ? 1 : 0.3}
                    style={{ transition: "opacity 0.18s" }}
                  />
                );
              }}
            />
          </PieChart>
        </ResponsiveContainer>

        {/* Valores apresentados no centro do gráfico. */}
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-5 text-center">
          <p className="text-xs uppercase tracking-wide text-slate-500">
            {center.label}
          </p>

          <p className="mt-1 text-3xl font-bold leading-none text-slate-900">
            {formatMoney(center.value)}
          </p>
        </div>
      </div>

      {/* Lista de categorias, percentagens e respetivos custos. */}
      <div className="w-full min-w-0 flex-1">
        {/* Maior gasto do período. */}
        {biggest && (
          <>
            <p className="text-sm text-slate-500">Maior gasto do período</p>

            <p className="mb-5 mt-1 text-2xl font-bold text-slate-900">
              {biggest.name} · {formatMoney(biggest.value)}
            </p>
          </>
        )}

        <ul className="flex flex-col gap-3">
          {data.map((item) => {
            const percentage =
              total !== 0 ? Math.round((item.value / total) * 100) : 0;

            const isFocused = focusedItem?.id === item.id;

            return (
              <li
                key={item.id}
                onMouseEnter={() => setFocusedId(item.id)}
                onMouseLeave={() => setFocusedId(null)}
                onFocus={() => setFocusedId(item.id)}
                onBlur={() => setFocusedId(null)}
                className={`flex items-center gap-3 rounded-lg px-2 py-2 transition ${
                  isFocused ? "bg-slate-50" : ""
                } ${onCategoryClick ? "cursor-pointer" : ""}`}
                onClick={() => onCategoryClick?.(item)}
                role={onCategoryClick ? "button" : undefined}
                tabIndex={onCategoryClick ? 0 : undefined}
                onKeyDown={(event) => {
                  if (
                    onCategoryClick &&
                    (event.key === "Enter" || event.key === " ")
                  ) {
                    event.preventDefault();
                    onCategoryClick(item);
                  }
                }}
              >
                <span
                  className="h-3 w-3 shrink-0 rounded-full"
                  style={{ backgroundColor: item.color }}
                />

                <span className="min-w-0 flex-1 text-sm text-slate-700">
                  {item.name}
                </span>

                <span className="text-sm text-slate-500">{percentage}%</span>

                <span className="w-24 text-right text-sm font-semibold text-slate-900">
                  {formatMoney(item.value)}
                </span>

                {onCategoryClick && (
                  <ChevronRight size={16} className="shrink-0 text-slate-400" />
                )}
              </li>
            );
          })}
        </ul>

        {/* Indicação de que as categorias permitem abrir detalhes. */}
        {onCategoryClick && (
          <p className="mt-5 text-xs text-slate-500">
            Clica numa categoria para consultar o detalhe.
          </p>
        )}
      </div>
    </div>
  );
}
