import { getNavItems } from "./navItems";
import { useAuth } from "../../hooks/useAuth";
import { SidebarLink } from "./SidebarItem";
import { AccountActions } from "./AccountActions";

export function SideBar() {
  const { activeProfile, activeHouse } = useAuth();

  return (
    <aside className="hidden md:flex fixed inset-y-0 left-0 z-30 w-60 flex-col overflow-y-auto bg-navy text-white p-4">
      <div className="font-display text-xl mb-8 px-2">Lar em Ordem</div>

      <nav className="flex flex-col gap-1">
        {getNavItems(activeProfile?.name, activeHouse).map((item) => (
          <SidebarLink
            key={item.path}
            to={item.path}
            icon={item.icon}
            label={item.label}
          />
        ))}
      </nav>

      <div className="mt-auto border-t border-white/10 pt-4">
        <AccountActions />
      </div>
    </aside>
  );
}
