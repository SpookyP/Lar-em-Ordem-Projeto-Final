import {
  Home,
  FolderClosed,
  Receipt,
  Zap,
  LifeBuoy,
  ClipboardList,
  FileText,
  Tag,
  Building2,
  Building,
} from "lucide-react";

//Lista com o items do menu de navegacao. A sidebar (desktop) e lowerbar (telemovel) leem desta lista
export const navItems = [
  { path: "/dashboard", label: "Inicio", icon: Home },
  { path: "/cofre", label: "Cofre", icon: FolderClosed },
  { path: "/condominium", label: "Condomínio", icon: Building, requiresCondominium: true },
  { path: "/faturas", label: "Faturas", icon: Receipt },
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

export const condominiumAdminNavItems = [
  { path: "/admin/condominiums", label: "Condomínios", icon: Building },
];

const navItemsByProfile = {
  service_provider: providerNavItems,
  partner: partnerNavItems,
  condominium_admin: condominiumAdminNavItems,
};

// items with requiresCondominium only show when the active home has joined a condominium
export function getNavItems(profileName, { hasCondominium = false } = {}) {
  const items = navItemsByProfile[profileName] ?? navItems;
  return items.filter((item) => !item.requiresCondominium || hasCondominium);
}
