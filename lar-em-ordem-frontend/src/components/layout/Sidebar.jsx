import { getNavItems } from "./navItems";
import { useAuth } from "../../hooks/useAuth";
import { SidebarLink } from "./SidebarItem";
import { AccountActions } from "./AccountActions";

export function SideBar() {
  const { activeProfile } = useAuth();

  return (
    <aside className="hidden md:flex w-60 flex-col bg-navy text-white p-4 h-screen sticky top-0 self-start">
      <div className="font-display text-xl mb-8 px-2">Lar em Ordem</div>

      <nav className="flex flex-col gap-1">
        {getNavItems(activeProfile?.name).map((item) => (
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
