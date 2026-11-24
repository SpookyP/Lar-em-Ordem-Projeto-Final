import {
  Banknote,
  Building2,
  FileText,
  Landmark,
  PenLine,
  Percent,
  Receipt,
  ScrollText,
  Shield,
  Stamp,
  Zap,
} from "lucide-react";

export const CHECKLIST_EXCLUDED_CATEGORIES = ["Outros"];

// how each category looks in the checklist, matched by the name the API sends;
// a category that is missing here still shows up, with the default look
export const CHECKLIST_META = {
  "Escritura": { group: "Registo", icon: ScrollText },
  "Certidão permanente do registo predial": {
    group: "Registo",
    icon: Building2,
    required: true,
  },
  "Caderneta predial": { group: "Fiscal", icon: Receipt, required: true },
  "Licença de utilização": {
    group: "Camarário",
    icon: Landmark,
    required: true,
  },
  "Certificado energético": { group: "Energia", icon: Zap, required: true },
  "Comprovativos de IMT": { group: "Fiscal", icon: Receipt },
  "Imposto do Selo": { group: "Fiscal", icon: Stamp },
  "Comprovativos de IMI": { group: "Fiscal", icon: Receipt },
  "Comprovativo de AIMI": { group: "Fiscal", icon: Receipt },
  "Contrato e mapa de amortizações": { group: "Crédito", icon: Banknote },
  "Declaração anual de juros": { group: "Crédito", icon: Percent },
  "Multirriscos habitação e seguro de vida": {
    group: "Seguros",
    icon: Shield,
    required: true,
  },
  "Contrato de arrendamento": { group: "Contratos", icon: PenLine },
  "Faturas": { group: "Consumos", icon: FileText },
};

export const DEFAULT_CHECKLIST_META = {
  group: "Geral",
  icon: FileText,
  required: false,
};