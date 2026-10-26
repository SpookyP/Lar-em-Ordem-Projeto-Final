import {Home, FolderClosed, Zap, LifeBuoy, ClipboardList, FileText} from 'lucide-react';

//Lista com o items do menu de navegacao. A sidebar (desktop) e lowerbar (telemovel) leem desta lista
export const navItems = [
    {path: '/dashboard', label: 'Inicio', icon: Home},
    {path: '/cofre', label: 'Cofre', icon: FolderClosed},
    {path: '/consumos', label: 'Consumos', icon: Zap},
    {path: '/assistencia', label: 'Assistência', icon: LifeBuoy}
];

export const providerNavItems = [
    {path: '/prestador/pedidos', label: 'Pedidos', icon: ClipboardList},
    {path: '/prestador/propostas', label: 'Propostas', icon: FileText}
];

const navItemsByProfile = {
    service_provider: providerNavItems
};

export function getNavItems(profileName) {
    return navItemsByProfile[profileName] ?? navItems;
}