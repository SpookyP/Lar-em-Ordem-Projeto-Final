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

const navItemsByProfile = {
  service_provider: providerNavItems,
  partner: partnerNavItems,
};

export function getNavItems(profileName, activeHouse) {
  //arrendatário (sem cofre)
  const byProfile = navItemsByProfile[profileName];
  if (byProfile) return byProfile;

  //se for proprietário
  const items = [...navItems];
  if (isOwnerHouse(activeHouse)) {
    items.splice(1, 0, { path: "/vault", label: "Cofre", icon: FolderClosed });
  }
  return items;
}
