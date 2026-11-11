// how each notice priority is shown (labels for people, colours from the theme)
export const NOTICE_PRIORITIES = {
  high: { label: "Alta", className: "bg-red-light text-red" },
  normal: { label: "Normal", className: "bg-navy/10 text-navy" },
  low: { label: "Baixa", className: "bg-surface text-muted" },
};

export function priorityInfo(priority) {
  return NOTICE_PRIORITIES[priority] ?? NOTICE_PRIORITIES.normal;
}

// "Nº 12 · 2º Esq"; a home without door or fraction still needs a readable name
export function fractionLabel(property) {
  return [property.door, property.fraction].filter(Boolean).join(" · ") || "Fração sem identificação";
}

export const PRIORITY_OPTIONS = [
  { value: "high", label: "Alta", description: "Assuntos urgentes, como cortes de água ou luz." },
  { value: "normal", label: "Normal" },
  { value: "low", label: "Baixa", description: "Informações sem urgência." },
];

// a notice without an end date stays current until it is deleted
export function isNoticeCurrent(notice, now = new Date()) {
  return !notice.expiresAt || new Date(notice.expiresAt) > now;
}