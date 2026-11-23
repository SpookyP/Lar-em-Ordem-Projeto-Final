import { keyToIndex, indexToKey } from "../../utils/invoiceTimelineUtils";

export const MAX_MONTH_DISTANCE = 24;

export const MONTHS = [
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

export const MONTHS_SHORT = [
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

export const monthLabel = (key) => MONTHS_SHORT[keyToIndex(key) % 12];

export const monthLongLabel = (key) =>
  `${MONTHS[keyToIndex(key) % 12]} ${key.slice(0, 4)}`;

export function presetRange(monthKeys, months) {
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
