import {
  Home,
  FolderClosed,
  Zap,
  LifeBuoy,
  ClipboardList,
  FileText,
  Tag,
  Building2,
} from "lucide-react";

//Lista com o items do menu de navegacao. A sidebar (desktop) e lowerbar (telemovel) leem desta lista
export const navItems = [
  { path: "/dashboard", label: "Inicio", icon: Home },
  { path: "/cofre", label: "Cofre", icon: FolderClosed },
  { path: "/consumos", label: "Consumos", icon: Zap },
  { path: "/assistance", label: "Assistência", icon: LifeBuoy },
  { path: "/partners", label: "Parceiros", icon: Building2 },
  { path: "/offers", label: "Ofertas", icon: Tag },
];

export const providerNavItems = [
  { path: "/provider/requests", label: "Pedidos", icon: ClipboardList },
  { path: "/provider/proposals", label: "Propostas", icon: FileText },
];

export const partnerNavItems = [
  { path: "/partner/offers", label: "Ofertas", icon: Tag },
];

const navItemsByProfile = {
  service_provider: providerNavItems,
  partner: partnerNavItems,
};

export function getNavItems(profileName) {
  return navItemsByProfile[profileName] ?? navItems;
}
