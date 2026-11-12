
import { useMemo } from "react";
import { Wallet, Zap, Droplets, Flame } from "lucide-react";

const SERVICE_TYPES = {
  1: "electricity",
  2: "water",
  3: "gas",
};

const DARKER_COLORS = {
  "#583D69": "#402C4D",
  "#1C3557": "#142840",
  "#0F766E": "#0A514D",
  "#EA580C": "#B84508",
};

function toNumber(value) {
  if (value === null || value === undefined || value === "") return 0;
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  let normalized = String(value).replace(/[€\s]/g, "");

  if (normalized.includes(",")) {
    normalized = normalized.replace(/\./g, "").replace(",", ".");
  }

  const number = Number(normalized);
  return Number.isFinite(number) ? number : 0;
}

function getTypeId(consumption) {
  const id =
    consumption?.consumption_type_id ??
    consumption?.consumption_type?.id;

  if (id && SERVICE_TYPES[id]) return Number(id);

  const name = String(
    consumption?.consumption_type?.name ??
    consumption?.type ??
    ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;
  if (name.includes("água") || name.includes("agua")) return 2;
  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

function getConsumptionItems(consumption) {
  const possibleItems = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return (
    possibleItems.find(
      (items) => Array.isArray(items) && items.length > 0
    ) ??
    possibleItems.find(Array.isArray) ??
    []
  );
}

function getConsumptionCost(consumption) {
  const directCost =
    consumption?.cost ??
    consumption?.total_cost ??
    consumption?.totalCost;

  if (directCost != null && directCost !== "") {
    return toNumber(directCost);
  }

  const items = getConsumptionItems(consumption);

  if (items.length > 0) {
    return items.reduce((total, item) => {
      return (
        total +
        toNumber(
          item?.cost ??
          item?.total_cost ??
          item?.totalCost ??
          item?.amount_cost ??
          item?.price
        )
      );
    }, 0);
  }

  return 0;
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "EUR",
  }).format(value);
}

export default function InvoiceCard({
  invoices = [],
  loading = false,
  error = "",
}) {
  const totals = useMemo(() => {
    const result = {
      total: 0,
      electricity: 0,
      water: 0,
      gas: 0,
    };

    invoices.forEach((invoice) => {
      result.total += toNumber(invoice?.total_amount);

      const consumptions = Array.isArray(invoice?.consumptions)
        ? invoice.consumptions
        : [];

      consumptions.forEach((consumption) => {
        const typeId = getTypeId(consumption);
        const service = SERVICE_TYPES[typeId];

        if (service) {
          result[service] += getConsumptionCost(consumption);
        }
      });
    });

    return result;
  }, [invoices]);

  const cards = [
    {
      title: "Despesa Total",
      value: totals.total,
      icon: Wallet,
      color: "#583D69",
    },
    {
      title: "Despesas de Eletricidade",
      value: totals.electricity,
      icon: Zap,
      color: "#1C3557",
    },
    {
      title: "Despesas de Água",
      value: totals.water,
      icon: Droplets,
      color: "#0F766E",
    },
    {
      title: "Despesas de Gás",
      value: totals.gas,
      icon: Flame,
      color: "#EA580C",
    },
  ];

  return (
    <div>
      {error && (
        <p className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
          {error}
        </p>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => {
          const Icon = card.icon;
          const darkerColor = DARKER_COLORS[card.color];

          return (
            <div
              key={card.title}
              className="group relative overflow-hidden rounded-2xl border border-white/10 p-5 shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
              style={{
                backgroundColor: card.color,
              }}
            >
              <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-semibold tracking-wide text-white/85">
                  {card.title}
                </p>

                <div
                  className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white transition-transform duration-300 group-hover:scale-110"
                  style={{
                    backgroundColor: darkerColor,
                  }}
                >
                  <Icon size={21} strokeWidth={2} />
                </div>
              </div>

              <div className="mt-5">
                <p className="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                  {loading
                    ? "..."
                    : error
                      ? "—"
                      : formatCurrency(card.value)}
                </p>

                <div className="mt-3 flex items-center gap-2">
                  <span
                    className="h-1.5 w-1.5 rounded-full"
                    style={{ backgroundColor: darkerColor }}
                  />
                  <span className="text-xs font-medium text-white/70">
                    Valor acumulado
                  </span>
                </div>
              </div>

              {/* Círculo decorativo mais escuro */}
              <div
                className="pointer-events-none absolute -bottom-8 -right-6 h-24 w-24 rounded-full opacity-40 transition-transform duration-500 group-hover:scale-125"
                style={{ backgroundColor: darkerColor }}
              />
            </div>
          );
        })}
      </div>
    </div>
  );
}
