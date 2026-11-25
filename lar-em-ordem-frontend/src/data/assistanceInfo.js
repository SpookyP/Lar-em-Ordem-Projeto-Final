import {
  Droplet,
  Zap,
  Thermometer,
  Hammer,
  PaintRoller,
  Plug,
  Sparkles,
  Trees,
  Wrench,
} from "lucide-react";

const WATER = "bg-teal-light text-teal";
const GAS = "bg-orange-light text-orange";
const ELECTRICITY = "bg-amber-light text-amber";
const GENERAL = "bg-sky-light text-sky";

const CATEGORY_INFO = {
  canalizacao: { icon: Droplet, color: WATER },
  climatizacao: { icon: Thermometer, color: GAS },
  eletricidade: { icon: Zap, color: ELECTRICITY },
  eletrodomesticos: { icon: Plug, color: ELECTRICITY },
  carpintaria: { icon: Hammer, color: GENERAL },
  pintura: { icon: PaintRoller, color: GAS },
  limpezas: { icon: Sparkles, color: WATER },
  jardinagem: { icon: Trees, color: WATER },
};
const FALLBACK = { icon: Wrench, color: GENERAL };
export const categoryInfo = (slug) => CATEGORY_INFO[slug] ?? FALLBACK;

const STATUS_INFO = {
  open: { label: "Pendente", style: "bg-sky-light text-sky" },
  closed: { label: "Proposta aceite", style: "bg-green-light text-green" },
  cancelled: { label: "Cancelado", style: "bg-red-light text-red" },
};

export const STATUS_LABELS = Object.fromEntries(
  Object.entries(STATUS_INFO).map(([key, info]) => [key, info.label]),
);

const PROVIDER_REQUEST_LABELS = {
  open: "Aberto a propostas",
  closed: "Fechado",
  cancelled: "Cancelado",
};

export const providerRequestLabel = (status) =>
  PROVIDER_REQUEST_LABELS[status] ?? status;

export const statusInfo = (status) =>
  STATUS_INFO[status] ?? { label: status, style: "bg-surface text-muted" };

export function slugify(text) {
  return text
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();
}

const PROPOSAL_FALLBACK = {
  label: "Desconhecido",
  style: "bg-surface text-muted",
};

export const PROPOSAL_STATUS = {
  pending: { label: "Pendente", style: "bg-amber-light text-amber" },
  accepted: { label: "Aceite", style: "bg-green-light text-green" },
  rejected: { label: "Rejeitada", style: "bg-red-light text-red" },
  withdrawn: { label: "Retirada", style: "bg-surface text-muted" },
};

const PROPOSAL_EXPIRED = { label: "Expirada", style: "bg-surface text-muted" };

export const proposalStatus = (status, isExpired = false) =>
  isExpired ? PROPOSAL_EXPIRED : (PROPOSAL_STATUS[status] ?? PROPOSAL_FALLBACK);
