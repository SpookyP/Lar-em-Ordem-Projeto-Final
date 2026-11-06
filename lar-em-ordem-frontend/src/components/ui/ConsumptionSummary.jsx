import { useMemo } from "react";
import { Droplet, Zap, Flame } from "lucide-react";
import { SummaryCard } from "./SummaryCard";

// Configuração visual e unidades de cada tipo de consumo.
const CONSUMPTION_CONFIG = {
  1: {
    name: "Eletricidade",
    icon: Zap,
    iconColor: "bg-amber-light text-amber",
    unit: "kWh",
  },
  2: {
    name: "Água",
    icon: Droplet,
    iconColor: "bg-teal-light text-teal",
    unit: "m³",
  },
  3: {
    name: "Gás",
    icon: Flame,
    iconColor: "bg-orange-light text-orange",
    unit: "kWh",
  },
};

const EMPTY_TOTALS = { 1: 0, 2: 0, 3: 0 };

// Converte valores numéricos, incluindo formatos decimais portugueses.
function parseAmount(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value).trim().replace(/\s/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const amount = Number(text);

  return Number.isFinite(amount) ? amount : 0;
}

// Converte datas para objetos Date e rejeita valores inválidos.
function parseDate(value) {
  if (!value) return null;

  if (typeof value === "string") {
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (match) {
      const [, year, month, day] = match;

      return new Date(
        Number(year),
        Number(month) - 1,
        Number(day)
      );
    }
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? null : date;
}

// Obtém a melhor data disponível para cada consumo.
function getConsumptionDate(consumption, invoice) {
  return (
    consumption?.period_start ??
    consumption?.period_end ??
    invoice?.period_start ??
    invoice?.period_end ??
    consumption?.issue_date ??
    invoice?.issue_date ??
    consumption?.created_at ??
    invoice?.created_at ??
    null
  );
}

// Determina a data inicial com base no período selecionado.
function getPeriodStart(period) {
  const now = new Date();

  return new Date(
    now.getFullYear(),
    now.getMonth() - Number(period),
    1
  );
}

// Identifica o tipo de consumo através do ID ou do nome.
function getConsumptionTypeId(consumption) {
  const raw =
    consumption?.consumption_type_id ??
    consumption?.consumptionTypeId ??
    consumption?.consumption_type?.id ??
    consumption?.consumptionType?.id ??
    consumption?.type_id ??
    consumption?.type?.id;

  const id = Number(raw);

  if (EMPTY_TOTALS[id] !== undefined) return id;

  const name = String(
    consumption?.consumption_type?.name ??
    consumption?.consumptionType?.name ??
    consumption?.type?.name ??
    consumption?.name ??
    ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;
  if (name.includes("água") || name.includes("agua") || name.includes("water")) return 2;
  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

// Obtém a quantidade consumida, considerando os diferentes formatos da API.
function getConsumptionAmount(consumption) {
  return parseAmount(
    consumption?.amount ??
    consumption?.quantity ??
    consumption?.consumption ??
    0
  );
}

export default function ConsumptionSummary({
  period = 1,
  invoices = [],
  loading = false,
  error = "",
}) {
  // Calcula os totais físicos de cada tipo de consumo no período selecionado.
  const totals = useMemo(() => {
    const newTotals = { ...EMPTY_TOTALS };

    const startDate = getPeriodStart(period);
    const endDate = new Date();

    invoices.forEach((invoice) => {
      const consumptions = Array.isArray(invoice?.consumptions)
        ? invoice.consumptions
        : [];

      consumptions.forEach((consumption) => {
        const date = parseDate(
          getConsumptionDate(consumption, invoice)
        );

        // Ignora consumos sem data válida ou fora do período.
        if (!date || date < startDate || date > endDate) return;

        const typeId = getConsumptionTypeId(consumption);

        if (!typeId) return;

        newTotals[typeId] += getConsumptionAmount(consumption);
      });
    });

    return newTotals;
  }, [invoices, period]);

  // Apresenta os totais calculados nos cartões de cada utilidade.
  return (
    <>
      {[1, 2, 3].map((typeId) => {
        const config = CONSUMPTION_CONFIG[typeId];
        const Icon = config.icon;

        return (
          <SummaryCard
            key={typeId}
            icon={Icon}
            iconColor={config.iconColor}
            value={
              loading
                ? "..."
                : error
                  ? "—"
                  : `${totals[typeId].toLocaleString("pt-PT", {
                      maximumFractionDigits: 2,
                    })} ${config.unit}`
            }
            label={`${config.name} · ${
              period === 1 ? "1 mês" : `${period} meses`
            }`}
          />
        );
      })}
    </>
  );
}
