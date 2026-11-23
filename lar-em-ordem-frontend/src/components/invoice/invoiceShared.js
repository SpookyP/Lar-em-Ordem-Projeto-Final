import { Zap, Droplets, Flame } from "lucide-react";
import {
  asNumber as toNumber,
  getConsumptions,
  getTypeId,
} from "../../utils/invoiceTimelineUtils";
import { formatMoney } from "../../utils/format";

export const HEADER_COLOR = "#1C3557";
export const HEADER_DARK_COLOR = "#142840";

export const SERVICE_TYPES = {
  1: {
    name: "Eletricidade",
    icon: Zap,
    color: "#1C3557",
  },
  2: {
    name: "Água",
    icon: Droplets,
    color: "#0F766E",
  },
  3: {
    name: "Gás",
    icon: Flame,
    color: "#EA580C",
  },
};

export const FILTER_COLORS = {
  all: "#583D69",
  1: "#1C3557",
  2: "#0F766E",
  3: "#EA580C",
};

export const DARKER_SERVICE_COLORS = {
  1: "#142840",
  2: "#0A514D",
  3: "#B84508",
};

export function getInvoiceTypes(invoice) {
  return [...new Set(getConsumptions(invoice).map(getTypeId).filter(Boolean))];
}

export function formatCurrency(value) {
  return formatMoney(toNumber(value));
}

export function formatDate(value) {
  if (!value) return "—";

  const dateString = String(value).slice(0, 10);
  let date;

  const isoMatch = dateString.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  const ptMatch = dateString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

  if (isoMatch) {
    date = new Date(
      Number(isoMatch[1]),
      Number(isoMatch[2]) - 1,
      Number(isoMatch[3]),
    );
  } else if (ptMatch) {
    date = new Date(
      Number(ptMatch[3]),
      Number(ptMatch[2]) - 1,
      Number(ptMatch[1]),
    );
  } else {
    date = new Date(dateString);
  }

  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("pt-PT").format(date);
}
