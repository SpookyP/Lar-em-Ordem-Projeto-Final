import { useMemo } from "react";
import { Wallet, Zap, Droplets, Flame } from "lucide-react";

// Mapeamento dos tipos de serviço.
const SERVICE_TYPES = {
  1: "electricity",
  2: "water",
  3: "gas",
};

// Converte valores numéricos, incluindo valores no formato português.
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

// Identifica o tipo de serviço associado ao consumo.
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

// Procura a lista de itens de consumo disponível.
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

// Obtém o custo do consumo ou calcula-o através dos seus itens.
function getConsumptionCost(consumption) {
  // Mantém a mesma prioridade do ConsumptionChart:
  // primeiro o custo do consumo e, na ausência dele, a soma dos itens.
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

// Formata os valores monetários em euros.
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
  // Calcula os totais gerais e os totais por serviço.
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

  // Dados utilizados para apresentar os cartões.
  const cards = [
    {
      title: "Despesa Total",
      value: totals.total,
      icon: Wallet,
      color: "bg-slate-100 text-slate-700",
    },
    {
      title: "Despesas de Eletricidade",
      value: totals.electricity,
      icon: Zap,
      color: "bg-blue-100 text-blue-700",
    },
    {
      title: "Despesas de Água",
      value: totals.water,
      icon: Droplets,
      color: "bg-teal-100 text-teal-700",
    },
    {
      title: "Despesas de Gás",
      value: totals.gas,
      icon: Flame,
      color: "bg-orange-100 text-orange-700",
    },
  ];

  // Apresentação dos cartões de resumo.
  return (
    <div>
      {error && (
        <p className="mb-4 text-sm text-red-600">{error}</p>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => {
          const Icon = card.icon;

          return (
            <div
              key={card.title}
              className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
              <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-slate-500">
                  {card.title}
                </p>

                <div className={`rounded-xl p-2.5 ${card.color}`}>
                  <Icon size={20} />
                </div>
              </div>

              <p className="mt-4 text-2xl font-bold text-slate-900">
                {loading
                  ? "..."
                  : error
                    ? "—"
                    : formatCurrency(card.value)}
              </p>
            </div>
          );
        })}
      </div>
    </div>
  );
}
