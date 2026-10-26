import { Droplet, Zap, Thermometer, Hammer, PaintRoller, Plug, Sparkles, Trees, Wrench } from "lucide-react";

export const CATEGORY_INFO = {
  canalizacao:      { icon: Droplet,     color: "bg-teal-light text-teal" },
  eletricidade:     { icon: Zap,         color: "bg-amber-light text-amber" },
  climatizacao:     { icon: Thermometer, color: "bg-orange-light text-orange" },
  carpintaria:      { icon: Hammer,      color: "bg-navy/10 text-navy" },
  pintura:          { icon: PaintRoller, color: "bg-navy/10 text-navy" },
  eletrodomesticos: { icon: Plug,        color: "bg-navy/10 text-navy" },
  limpezas:         { icon: Sparkles,    color: "bg-teal-light text-teal" },
  jardinagem:       { icon: Trees,       color: "bg-teal-light text-teal" },
};
const FALLBACK = { icon: Wrench, color: "bg-navy/10 text-navy" };
export const categoryInfo = (slug) => CATEGORY_INFO[slug] ?? FALLBACK;

export const STATUS_LABELS = {
  open: "Pendente",
  closed: "Proposta aceite",
  cancelled: "Cancelado",
};

export function slugify(text) {
  return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

const PROPOSAL_FALLBACK = { label: "Desconhecido", style: "bg-surface text-muted" };

export const PROPOSAL_STATUS = {
  pending: { label: "Pendente", style: "bg-amber-light text-amber" },
  accepted: { label: "Aceite", style: "bg-green-light text-green" },
  rejected: { label: "Rejeitada", style: "bg-red-light text-red" },
  withdrawn: { label: "Retirada", style: "bg-surface text-muted" },
};

export const proposalStatus = (status) =>
  PROPOSAL_STATUS[status] ?? PROPOSAL_FALLBACK;