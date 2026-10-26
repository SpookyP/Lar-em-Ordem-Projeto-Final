import { NavLink } from "react-router-dom";
import { getNavItems } from "./navItems";
import { useAuth } from "../../hooks/useAuth";
import { useState } from "react";
import { AccountDrawer } from "./AccountDrawer";
import { User } from "lucide-react";

export function BottomNav() {
  const { activeProfile } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <>
      <nav className="md:hidden fixed bottom-0 left-0 right-0 z-40 flex h-16 items-center justify-around border-t border-border bg-card">
        {getNavItems(activeProfile?.name).map((item) => {
          const Icon = item.icon;
          return (
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) =>
                `flex flex-col items-center gap-1 py-2 px-3 text-xs transition ${
                  isActive ? "text-navy" : "text-muted"
                }`
              }
            >
              <Icon size={20} />
              {item.label}
            </NavLink>
          );
        })}

        <button
          onClick={() => setMenuOpen(true)}
          className="flex flex-col items-center gap-1 py-2 px-3 text-xs text-muted transition"
        >
          <User size={20} />
          Conta
        </button>
      </nav>

      <AccountDrawer open={menuOpen} onClose={() => setMenuOpen(false)} />
    </>
  );
}
