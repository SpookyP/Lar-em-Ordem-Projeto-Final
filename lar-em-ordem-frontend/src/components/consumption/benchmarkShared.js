import { FileText, Zap, Droplets, Flame } from "lucide-react";

export const HEADER_COLOR = "#1C3557";

export function getServiceTheme(option) {
  const value = String(option?.value ?? "").toLowerCase();
  const label = String(option?.label ?? "").toLowerCase();

  const raw = `${value} ${label}`
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  if (
    /^(all|general|geral|todos)$/.test(value) ||
    /\b(all|general|geral|todos)\b/.test(raw)
  ) {
    return {
      Icon: FileText,
      kind: "all",
      color: "#583D69",
      iconClass: "text-[#583D69]",
      selectedClass: "border-white bg-white text-[#583D69] shadow-sm",
      chipClass: "border-slate-200 bg-slate-50 text-slate-700",
    };
  }

  if (value === "1" || /eletric|electric|energia/.test(raw)) {
    return {
      Icon: Zap,
      kind: "electricity",
      color: "#1C3557",
      iconClass: "text-navy",
      selectedClass: "border-white bg-white text-navy shadow-sm",
      chipClass: "border-navy/20 bg-navy/5 text-navy",
    };
  }

  if (value === "2" || /agua|water/.test(raw)) {
    return {
      Icon: Droplets,
      kind: "water",
      color: "#0F766E",
      iconClass: "text-[#0F766E]",
      selectedClass: "border-white bg-white text-[#0F766E] shadow-sm",
      chipClass: "border-[#0F766E]/20 bg-[#0F766E]/5 text-[#0F766E]",
    };
  }

  if (value === "3" || /gas/.test(raw)) {
    return {
      Icon: Flame,
      kind: "gas",
      color: "#EA580C",
      iconClass: "text-[#EA580C]",
      selectedClass: "border-white bg-white text-[#EA580C] shadow-sm",
      chipClass: "border-[#EA580C]/20 bg-[#EA580C]/5 text-[#EA580C]",
    };
  }

  return {
    Icon: FileText,
    kind: "other",
    color: "#64748b",
    iconClass: "text-slate-500",
    selectedClass: "border-white bg-white text-slate-600 shadow-sm",
    chipClass: "border-slate-200 bg-slate-50 text-slate-700",
  };
}

export function isGeneralService(option) {
  return getServiceTheme(option).kind === "all";
}
