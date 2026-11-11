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

// order low -> high, as they read left to right; activeClass colours the chosen pill
export const PRIORITY_OPTIONS = [
  {
    value: "low",
    label: "Baixa",
    description: "Informações sem urgência.",
    activeClass: "data-checked:bg-card data-checked:text-text",
  },
  {
    value: "normal",
    label: "Normal",
    description: "Avisos do dia a dia.",
    activeClass: "data-checked:bg-navy data-checked:text-white",
  },
  {
    value: "high",
    label: "Alta",
    description: "Assuntos urgentes, como cortes de água ou luz.",
    activeClass: "data-checked:bg-red data-checked:text-white",
  },
];

// a notice without an end date stays current until it is deleted
export function isNoticeCurrent(notice, now = new Date()) {
  return !notice.expiresAt || new Date(notice.expiresAt) > now;
}