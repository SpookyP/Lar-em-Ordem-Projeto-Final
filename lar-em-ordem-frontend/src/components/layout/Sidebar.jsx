import { useNavigate } from "react-router-dom";
import { navItems } from "./navItems";
import { LogOut, Settings, Repeat, Home } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";
import { getProperties } from "../../services/api/propertyService";
import { useState, useEffect } from "react";
import { SidebarButton, SidebarLink } from "./SidebarItem";

function getInitials(text) {
  if (!text) return "?";
  const parts = text.trim().split(" ");
  const first = parts[0]?.[0] ?? "";
  const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
  return (first + last).toUpperCase();
}

function firstLastName(name) {
  if (!name) return "Conta";
  const parts = name.trim().split(" ").filter(Boolean);
  if (parts.length === 1) return parts[0];
  return `${parts[0]} ${parts[parts.length - 1]}`;
}

export function SideBar() {
  const { user, activeProfile, logout } = useAuth();
  const navigate = useNavigate();
  const [housesCount, setHousesCount] = useState(0);

  const profiles = (user?.roles ?? []).map((roleName) => ({
    name: roleName,
    label: ROLE_INFO[roleName]?.label ?? roleName,
    hasHouses: ROLE_INFO[roleName]?.hasHouses ?? false,
  }));

  useEffect(() => {
    let active = true;
    if (activeProfile?.hasHouses) {
      getProperties().then((data) => {
        if (active) setHousesCount(data.total);
      });
    } else {
      setHousesCount(0);
    }
    return () => {
      active = false;
    };
  }, [activeProfile]);

  async function handleLogout() {
    await logout();
    navigate("/login");
  }

  return (
    <aside className="hidden md:flex w-60 flex-col bg-navy text-white p-4 h-screen sticky top-0 self-start">
      <div className="font-display text-xl mb-8 px-2">Lar em Ordem</div>

      <nav className="flex flex-col gap-1">
        {navItems.map((item) => (
          <SidebarLink
            key={item.path}
            to={item.path}
            icon={item.icon}
            label={item.label}
          />
        ))}
      </nav>

      <div className="mt-auto flex flex-col gap-2 border-t border-white/10 pt-4">
        {/* Perfil ativo */}
        <div className="flex items-center gap-3 rounded-lg bg-navy-light/50 px-3 py-2">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal text-sm font-bold text-white">
            {getInitials(user?.name)}
          </div>
          <p className="truncate text-sm font-medium text-white">
            {firstLastName(user?.name)}
          </p>
        </div>

        {/* Trocar de casa */}
        {activeProfile?.hasHouses && housesCount > 1 && (
          <SidebarButton
            icon={Home}
            label="As minhas casas"
            onClick={() => navigate("/perfis")}
          />
        )}

        {/* Trocar de Perfil */}
        {profiles.length > 1 && (
          <SidebarButton
            icon={Repeat}
            label="Trocar de perfil"
            onClick={() => navigate("/perfis")}
          />
        )}

        <SidebarLink
          icon={Settings}
          label="Definições de conta"
          to="/definicoes"
        />

        <SidebarButton
          icon={LogOut}
          label="Terminar sessão"
          onClick={handleLogout}
        />
      </div>
    </aside>
  );
}
