import { useNavigate } from "react-router-dom";
import { Home, Repeat, Settings, LogOut } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { useHouses } from "../../hooks/useHouses";
import { ROLE_INFO } from "../../data/roleInfo";
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

export function AccountActions({ onAction }) {
  const { user, activeProfile, logout } = useAuth();
  const { count } = useHouses();
  const navigate = useNavigate();

  const profiles = (user?.roles ?? []).map((roleName) => ({
    name: roleName,
    label: ROLE_INFO[roleName]?.label ?? roleName,
    hasHouses: ROLE_INFO[roleName]?.hasHouses ?? false,
  }));

  function go(path) {
    navigate(path);
    onAction?.(); //fecha o drawer no mobile
  }

  async function handleLogout() {
    await logout();
    navigate("/login");
    onAction?.();
  }

  return (
    <div className="flex flex-col gap-2">
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
      {activeProfile?.hasHouses && count > 1 && (
        <SidebarButton
          icon={Home}
          label="As minhas casas"
          onClick={() => go(`/perfis?perfil=${activeProfile.name}`)}
        />
      )}

      {/* Trocar de perfil */}
      {profiles.length > 1 && (
        <SidebarButton
          icon={Repeat}
          label="Trocar de perfil"
          onClick={() => go("/perfis")}
        />
      )}

      <SidebarLink
        icon={Settings}
        label="Definições de conta"
        to="/definicoes"
        onClick={onAction}
      />

      <SidebarButton
        icon={LogOut}
        label="Terminar sessão"
        onClick={handleLogout}
      />
    </div>
  );
}
