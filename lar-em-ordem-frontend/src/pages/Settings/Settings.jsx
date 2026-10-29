import { useState } from "react";
import { PropertyForm } from "../../components/ui/PropertyForm";
import { PasswordForm } from "../../components/ui/PasswordForm";
import { ProfileForm } from "../../components/ui/ProfileForm";
import {
  User,
  KeyRound,
  UserPlus,
  House,
  HousePlus,
  Pencil,
  UserMinus,
  Trash2,
} from "lucide-react";
import { AccountForm } from "../../components/ui/AccountForm";
import { HouseDetails } from "../../components/ui/HouseDetails";
import { ManageHouses } from "../../components/ui/ManageHouses";
import { DeleteProfile } from "../../components/ui/DeleteProfile";
import { DeleteAccount } from "../../components/ui/DeleteAccount";
import { useAuth } from "../../hooks/useAuth";

const GROUPS = [
  {
    title: "Secção do utilizador",
    items: [
      { id: "account", label: "Os meus dados", icon: User },
      { id: "password", label: "Mudar password", icon: KeyRound },
      { id: "profile", label: "Adicionar perfil", icon: UserPlus },
      { id: "delete-profile", label: "Apagar perfil", icon: UserMinus },
    ],
  },
  {
    title: "Secção da habitação",
    requiresHouses: true,
    items: [
      { id: "house", label: "A minha habitação", icon: House },
      { id: "property", label: "Adicionar habitação", icon: HousePlus },
      { id: "manage-house", label: "Gerir habitações", icon: Pencil },
    ],
  },
  {
    title: "Gestão da conta",
    items: [{ id: "delete-account", label: "Apagar conta", icon: Trash2 }],
  },
];

export default function Settings() {
  const [section, setSection] = useState(null);
  const { activeProfile } = useAuth();
  const canHaveHouses = activeProfile?.hasHouses ?? false;

  const visibleGroups = GROUPS.filter(
    (g) => !g.requiresHouses || canHaveHouses,
  );

  return (
    <div className="p-6">
      <h1 className="font-display text-3xl text-navy mb-6">
        Definições de conta
      </h1>

      <div className="flex flex-col md:flex-row gap-6">
        {/*Menu esq. */}
        <nav className="w-full md:w-64 shrink-0 flex flex-col gap-6">
          {visibleGroups.map((group) => (
            <div key={group.title} className="flex flex-col gap-2">
              <p className="px-1 text-sm font-semibold uppercase tracking-wide text-muted">
                {group.title}
              </p>
              <div className="flex flex-col gap-2 pl-3">
                {group.items.map((item) => {
                  const Icon = item.icon;
                  const isActive = section === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setSection(item.id)}
                      className={`flex items-center gap-3 rounded-xl border p-4 text-left text-sm transition ${
                        isActive
                          ? "border-navy bg-navy text-white"
                          : "border-border bg-card text-text hover:bg-surface"
                      }`}
                    >
                      <Icon size={20} />
                      {item.label}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        {/*Menu dta. */}
        <div className="flex-1 max-w-3xl rounded-3xl bg-card border border-border overflow-hidden max-h-[80vh]">
          <div className="h-full overflow-y-auto p-6">
            {section === null && (
              <p className="text-sm text-muted">Seleciona uma opção.</p>
            )}
            {section === "account" && <AccountForm />}
            {section === "password" && <PasswordForm />}
            {section === "profile" && <ProfileForm />}
            {section === "house" && <HouseDetails />}
            {section === "property" && <PropertyForm />}
            {section === "manage-house" && <ManageHouses />}
            {section === "delete-profile" && <DeleteProfile />}
            {section === "delete-account" && <DeleteAccount />}
          </div>
        </div>
      </div>
    </div>
  );
}
