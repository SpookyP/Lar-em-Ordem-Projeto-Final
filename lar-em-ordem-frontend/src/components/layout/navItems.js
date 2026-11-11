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
import { isOwnerHouse } from "../../utils/ownerHouse";

//Lista com o items do menu de navegacao. A sidebar (desktop) e mobileNav (telemovel) leem desta lista
export const navItems = [
  { path: "/dashboard", label: "Inicio", icon: Home },
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

export function getNavItems(profileName, activeHouse, { hasCondominium = false } = {}) {
  //arrendatário (sem cofre)
  const byProfile = navItemsByProfile[profileName];
  if (byProfile) return byProfile;

  const items = [...navItems];

  //se for proprietário
  if (isOwnerHouse(activeHouse)) {
    items.splice(1, 0, { path: "/vault", label: "Cofre", icon: FolderClosed });
  }

  // only when the active home has joined a condominium (joining is done in the settings)
  if (hasCondominium) {
    const afterVault = items.findIndex((item) => item.path === "/vault") + 1 || 1;
    items.splice(afterVault, 0, { path: "/condominio", label: "Condomínio", icon: Building });
  }

  return items;
}