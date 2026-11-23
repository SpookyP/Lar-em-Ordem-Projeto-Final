import { asNumber } from "../../utils/invoiceTimelineUtils";
import { formatMoney } from "../../utils/format";

export const emptyItem = () => ({
  name: "",
  category: "consumption",
  amount: "",
  unit: "",
  cost: "",
});

export const numberValue = (v) => asNumber(v);

export const isBlank = (v) => v == null || String(v).trim() === "";

export const toInputValue = (v) => (isBlank(v) ? "" : String(asNumber(v)));

export const sumInputs = (a, b) =>
  String(Number((asNumber(a) + asNumber(b)).toFixed(4)));

export const readActiveHouseId = () => {
  if (typeof window === "undefined") return "";

  try {
    const stored = window.localStorage.getItem("activeHouse");
    if (!stored) return "";

    try {
      const parsed = JSON.parse(stored);
      if (typeof parsed === "string") return parsed;
      if (typeof parsed === "number") return String(parsed);
      if (parsed && typeof parsed === "object") return parsed.id ?? "";
      return "";
    } catch {
      return stored;
    }
  } catch {
    return "";
  }
};

export const itemCategoryOptions = [
  { value: "consumption", label: "Consumo" },
  { value: "fixed_fee", label: "Tarifa fixa" },
  { value: "tax", label: "Imposto / taxa" },
  { value: "discount", label: "Desconto" },
  { value: "other", label: "Outro" },
];

const DAY_MS = 24 * 60 * 60 * 1000;
const MAX_PERIOD_DAYS = 400;

export function validateInvoice({
  formData,
  selectedTypeIds,
  consumptionsData,
  showItems,
  itemsData,
  consumptionTypes,
}) {
  if (isBlank(formData.supplier) || isBlank(formData.invoice_number)) {
    return "Preenche o fornecedor e o número da fatura.";
  }

  if (isBlank(formData.total_amount)) {
    return "Preenche o valor total da fatura.";
  }

  const start = Date.parse(formData.period_start);
  const end = Date.parse(formData.period_end);

  if (Number.isFinite(start) && Number.isFinite(end)) {
    if (end < start) {
      return "O fim do período não pode ser anterior ao início.";
    }

    if ((end - start) / DAY_MS > MAX_PERIOD_DAYS) {
      return `O período de faturação não pode exceder ${MAX_PERIOD_DAYS} dias. Confirma as datas.`;
    }
  }

  if (selectedTypeIds.length === 0) {
    return "Seleciona pelo menos um tipo de consumo.";
  }

  for (const id of selectedTypeIds) {
    const name =
      consumptionTypes.find((t) => Number(t.id) === id)?.name ?? "consumo";
    const data = consumptionsData[id] ?? {};

    if (isBlank(data.amount) || isBlank(data.cost)) {
      return `Preenche a quantidade e o custo total de ${name}.`;
    }

    if (showItems[id]) {
      const items = (itemsData[id] || []).filter((item) => item.name.trim());

      if (items.length > 0) {
        const itemsTotal = items.reduce(
          (sum, item) => sum + numberValue(item.cost),
          0,
        );
        const consumptionCost = numberValue(data.cost);

        if (Math.abs(itemsTotal - consumptionCost) > 0.05) {
          return `Os itens de ${name} somam ${formatMoney(itemsTotal)}, mas o custo total indicado é ${formatMoney(consumptionCost)}.`;
        }
      }
    }
  }
  return null;
}
