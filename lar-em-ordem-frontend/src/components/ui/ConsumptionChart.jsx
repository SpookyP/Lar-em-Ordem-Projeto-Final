
import { useEffect, useMemo, useState } from "react";
import axiosClient from "../../services/api/axiosClient";
import {
  PieChart,
  Pie,
  ResponsiveContainer,
  Sector,
} from "recharts";
import { ChevronRight } from "lucide-react";

const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

function asNumber(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value)
    .trim()
    .replace(/\s/g, "")
    .replace(/[€]/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);
  return Number.isFinite(result) ? result : 0;
}

function responseArray(response) {
  const body = response?.data ?? response;

  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;
  if (Array.isArray(body?.data?.data)) return body.data.data;

  return [];
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
      ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) {
    return 1;
  }

  if (
    name.includes("água") ||
    name.includes("agua") ||
    name.includes("water")
  ) {
    return 2;
  }

  if (name.includes("gás") || name.includes("gas")) {
    return 3;
  }

  return null;
}

function getItems(consumption) {
  const possible = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return possible.find(Array.isArray) ?? [];
}

function getItemCost(item) {
  return asNumber(
    item?.cost ??
      item?.total_cost ??
      item?.totalCost ??
      item?.amount_cost ??
      item?.price ??
      0
  );
}

function getConsumptionCost(consumption) {
  const items = getItems(consumption);

  // Se existem linhas detalhadas, somamos as linhas
  // para não duplicar o total do consumo.
  if (items.length > 0) {
    return items.reduce(
      (sum, item) => sum + getItemCost(item),
      0
    );
  }

  return asNumber(
    consumption?.cost ??
      consumption?.total_cost ??
      consumption?.totalCost ??
      0
  );
}

function normalizeInvoices(invoices) {
  const totals = {
    1: 0,
    2: 0,
    3: 0,
  };

  invoices.forEach((invoice) => {
    const consumptions =
      invoice?.consumptions ??
      invoice?.utilities ??
      [];

    if (!Array.isArray(consumptions)) return;

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

export default function ConsumptionChart({
  propertyId,
  refreshKey = 0,
  invoices: invoicesProp,
  onCategoryClick,
}) {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [focused, setFocused] = useState(null);

  useEffect(() => {
    let cancelled = false;

    async function loadInvoices() {
      if (Array.isArray(invoicesProp)) {
        setInvoices(invoicesProp);
        setLoading(false);
        setError("");
        return;
      }

      setLoading(true);
      setError("");

      try {
        const response = await axiosClient.get(
          "/api/v1/invoices",
          {
            params: propertyId
              ? { property_id: propertyId }
              : {},
          }
        );

        if (!cancelled) {
          setInvoices(responseArray(response));
        }
      } catch (err) {
        if (!cancelled) {
          setInvoices([]);
          setError(
            err?.response?.data?.message ??
              "Não foi possível carregar os consumos."
          );
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    loadInvoices();

    return () => {
      cancelled = true;
    };
  }, [propertyId, refreshKey, invoicesProp]);

  const data = useMemo(
    () => normalizeInvoices(invoices),
    [invoices]
  );

  const total = useMemo(
    () => data.reduce((sum, item) => sum + item.value, 0),
    [data]
  );

  const focusedItem =
    focused === null ? null : data[focused] ?? null;

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
    (max, item) =>
      !max || item.value > max.value ? item : max,
    null
  );

  if (loading) {
    return (
      <div className="flex min-h-40 items-center justify-center">
        <p className="text-sm text-slate-500">
          A carregar consumos...
        </p>
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
          Adiciona uma fatura para consultar a distribuição
          dos custos.
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center gap-8 lg:flex-row lg:gap-12">
      {/* Gráfico de anel */}
      <div
        className="relative shrink-0"
        style={{ width: 260, height: 260 }}
      >
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
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
              onMouseEnter={(_, index) => setFocused(index)}
              onMouseLeave={() => setFocused(null)}
              shape={(props) => {
                const { index } = props;
                const isFocused = focused === index;

                return (
                  <Sector
                    {...props}
                    fill={data[index]?.color}
                    opacity={
                      focused === null || isFocused ? 1 : 0.3
                    }
                    style={{ transition: "opacity 0.18s" }}
                  />
                );
              }}
            />
          </PieChart>
        </ResponsiveContainer>

        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-5 text-center">
          <p className="text-xs uppercase tracking-wide text-slate-500">
            {center.label}
          </p>
          <p className="mt-1 text-3xl font-bold leading-none text-slate-900">
            {money.format(center.value)}
          </p>
        </div>
      </div>

      {/* Legenda dinâmica */}
      <div className="w-full min-w-0 flex-1">
        {biggest && (
          <>
            <p className="text-sm text-slate-500">
              Maior gasto do período
            </p>
            <p className="mb-5 mt-1 text-2xl font-bold text-slate-900">
              {biggest.name} · {money.format(biggest.value)}
            </p>
          </>
        )}

        <ul className="flex flex-col gap-3">
          {data.map((item, index) => {
            const percent =
              total !== 0
                ? Math.round((item.value / total) * 100)
                : 0;

            const isFocused = focused === index;

            return (
              <li
                key={item.id}
                onMouseEnter={() => setFocused(index)}
                onMouseLeave={() => setFocused(null)}
                className={`flex items-center gap-3 rounded-lg px-2 py-2 transition ${
                  isFocused ? "bg-slate-50" : ""
                } ${
                  onCategoryClick ? "cursor-pointer" : ""
                }`}
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

                <span className="text-sm text-slate-500">
                  {percent}%
                </span>

                <span className="w-24 text-right text-sm font-semibold text-slate-900">
                  {money.format(item.value)}
                </span>

                {onCategoryClick && (
                  <ChevronRight
                    size={16}
                    className="shrink-0 text-slate-400"
                  />
                )}
              </li>
            );
          })}
        </ul>

        {onCategoryClick && (
          <p className="mt-5 text-xs text-slate-500">
            Clica numa categoria para consultar o detalhe.
          </p>
        )}
      </div>
    </div>
  );
}
