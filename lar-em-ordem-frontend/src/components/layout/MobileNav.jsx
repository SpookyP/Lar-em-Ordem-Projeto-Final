import { useEffect, useState } from "react";
import { Menu, X } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { getNavItems } from "./navItems";
import { SidebarLink } from "./SidebarItem";
import { AccountActions } from "./AccountActions";

export function MobileNav() {
  const { activeProfile, activeHouse } = useAuth();
  const [open, setOpen] = useState(false);
  const close = () => setOpen(false);

  useEffect(() => {
    if (!open) return;
    function onKey(e) {
      if (e.key === "Escape") setOpen(false);
    }
    window.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [open]);

  return (
    <>
      <header className="md:hidden sticky top-0 z-30 flex h-14 items-center justify-between border-b border-border bg-card px-4">
        <span className="font-display text-lg text-navy">Lar em Ordem</span>
        <button
          type="button"
          onClick={() => setOpen(true)}
          aria-label="Abrir menu"
          className="rounded-lg p-2 text-navy transition hover:bg-surface"
        >
          <Menu size={24} />
        </button>
      </header>

      {open && (
        <div className="md:hidden fixed inset-0 z-50">
          <div className="absolute inset-0 bg-black/50" onClick={close} />

          <aside className="absolute inset-y-0 left-0 flex w-[85%] max-w-xs flex-col bg-navy p-4 text-white shadow-xl">
            <div className="mb-8 flex items-center justify-between px-2">
              <span className="font-display text-xl">Lar em Ordem</span>
              <button
                type="button"
                onClick={close}
                aria-label="Fechar menu"
                className="rounded-lg p-1 text-white/80 transition hover:bg-white/10"
              >
                <X size={22} />
              </button>
            </div>

            <nav className="flex flex-col gap-1">
              {getNavItems(activeProfile?.name, activeHouse).map((item) => (
                <SidebarLink
                  key={item.path}
                  to={item.path}
                  icon={item.icon}
                  label={item.label}
                  onClick={close}
                />
              ))}
            </nav>

            <div className="mt-auto border-t border-white/10 pt-4">
              <AccountActions onAction={close} />
            </div>
          </aside>
        </div>
      )}
    </>
  );
}
