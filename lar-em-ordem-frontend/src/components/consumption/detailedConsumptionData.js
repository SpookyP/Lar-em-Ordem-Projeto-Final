import {
  asNumber,
  getItems,
  getTypeId,
  itemCost,
} from "../../utils/invoiceTimelineUtils";
import { formatNumber } from "../../utils/format";

export const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

const CATEGORY_LABELS = {
  consumption: "Consumo",
  fixed_fee: "Tarifa fixa",
  tax: "Imposto / taxa",
  discount: "Desconto",
  other: "Outro",
};

function itemAmount(item) {
  return asNumber(item?.amount ?? item?.quantity ?? item?.consumption ?? 0);
}

function itemUnit(item) {
  return String(
    item?.unit ?? item?.measurement_unit ?? item?.unit_of_measure ?? "",
  ).trim();
}

function itemName(item, index) {
  return String(
    item?.name ??
      item?.description ??
      item?.label ??
      item?.concept ??
      item?.category ??
      `Item ${index + 1}`,
  ).trim();
}

function itemDescription(item) {
  const description = String(
    item?.description ??
      item?.details ??
      item?.notes ??
      item?.tariff ??
      item?.rate_name ??
      "",
  ).trim();

  const rawCategory = String(item?.category ?? "").trim();
  const category = CATEGORY_LABELS[rawCategory] ?? rawCategory;
  const amount = itemAmount(item);
  const unit = itemUnit(item);

  const quantityText =
    amount !== 0
      ? `${formatNumber(amount, { decimals: 3 })}${unit ? ` ${unit}` : ""}`
      : "";

  const name = String(item?.name ?? "").trim();

  return [
    description && description !== name ? description : "",
    category && category !== name ? category : "",
    quantityText,
  ]
    .filter(Boolean)
    .filter((part, index, array) => array.indexOf(part) === index)
    .join(" · ");
}

function normalizeItemName(name) {
  return String(name ?? "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");
}

export function normalizeInvoices(invoices) {
  const result = [];

  (invoices ?? []).forEach((invoice, invoiceIndex) => {
    const consumptions = invoice?.consumptions ?? invoice?.utilities ?? [];

    if (!Array.isArray(consumptions)) return;

    const invoiceKey = String(invoice?.id ?? `invoice-${invoiceIndex}`);

    consumptions.forEach((consumption, consumptionIndex) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      const utility = UTILITIES[typeId];
      const items = getItems(consumption);

      const pushItem = (item, itemIndex, cost, fallback = false) => {
        const amount = fallback
          ? asNumber(consumption?.amount ?? consumption?.quantity)
          : itemAmount(item);

        const unit = fallback ? itemUnit(consumption) : itemUnit(item);

        result.push({
          id: [
            invoice?.id ?? invoiceIndex,
            consumption?.id ?? consumptionIndex,
            item?.id ?? itemIndex,
          ].join("-"),
          invoiceKey,
          typeId,
          utility: utility.name,
          color: utility.color,
          name: fallback
            ? `${utility.name} — total`
            : itemName(item, itemIndex),
          description: fallback
            ? amount !== 0
              ? `${formatNumber(amount, { decimals: 3 })}${unit ? ` ${unit}` : ""} · Total do consumo`
              : "Total do consumo"
            : itemDescription(item),
          categoryLabel: fallback
            ? ""
            : (CATEGORY_LABELS[item?.category] ?? item?.category ?? ""),
          amount,
          unit,
          cost,
          sliceValue: Math.abs(cost),
          isCredit: cost < 0,
        });
      };

      if (items.length) {
        items.forEach((item, itemIndex) => {
          pushItem(item, itemIndex, itemCost(item));
        });
      } else {
        pushItem(
          consumption,
          0,
          asNumber(
            consumption?.cost ??
              consumption?.total_cost ??
              consumption?.totalCost ??
              0,
          ),
          true,
        );
      }
    });
  });

  const grouped = new Map();

  result.forEach((item) => {
    const key = `${item.typeId}-${normalizeItemName(item.name)}`;

    if (!grouped.has(key)) {
      grouped.set(key, {
        ...item,
        invoiceKeys: item.invoiceKey ? [item.invoiceKey] : [],
        descriptions: item.description ? [item.description] : [],
      });

      return;
    }

    const existing = grouped.get(key);

    existing.cost += item.cost;
    existing.amount += item.amount;

    if (item.invoiceKey && !existing.invoiceKeys.includes(item.invoiceKey)) {
      existing.invoiceKeys.push(item.invoiceKey);
    }

    if (item.description && !existing.descriptions.includes(item.description)) {
      existing.descriptions.push(item.description);
    }
  });

  return Array.from(grouped.values())
    .map((item) => {
      const quantityText =
        item.amount !== 0
          ? `${formatNumber(item.amount, { decimals: 3 })}${item.unit ? ` ${item.unit}` : ""}`
          : "";

      const descriptionParts = [
        ...item.descriptions
          .flatMap((description) => description.split(" · "))
          .filter((part) => {
            const value = part.trim().toLowerCase();

            const isQuantity =
              /^[\d.,]+\s(kwh|mwh|m3|m³|l|litros?|kw|kva)$/i.test(value);

            const isTaxBase = /^[\d.,]+\s(eur|€)\s+de\s+base$/i.test(value);

            return !isQuantity && !isTaxBase;
          }),
        quantityText,
      ]
        .map((part) => String(part ?? "").trim())
        .filter(Boolean)
        .filter(
          (part, index, array) =>
            array.findIndex(
              (value) => value.toLowerCase() === part.toLowerCase(),
            ) === index,
        );

      const groupedItem = { ...item };
      delete groupedItem.invoiceKeys;
      delete groupedItem.invoiceKey;

      return {
        ...groupedItem,
        id: `${item.typeId}-${normalizeItemName(item.name)}`,
        sliceValue: Math.abs(item.cost),
        isCredit: item.cost < 0,
        description: descriptionParts.join(" · "),
        invoiceCount: item.invoiceKeys.length,
      };
    })
    .sort((a, b) =>
      a.typeId !== b.typeId
        ? a.typeId - b.typeId
        : a.name.localeCompare(b.name, "pt-PT"),
    );
}
