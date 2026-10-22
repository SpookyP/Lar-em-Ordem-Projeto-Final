import { useState } from "react";
import { PropertyForm } from "../../components/ui/PropertyForm";
import { PasswordForm } from "../../components/ui/PasswordForm";
import { ProfileForm } from "../../components/ui/ProfileForm";
import { User, KeyRound, UserPlus, Home } from "lucide-react";
import { AccountForm } from "../../components/ui/AccountForm";

const SECTIONS = [
  { id: "password", label: "Mudar password", icon: KeyRound },
  { id: "profile", label: "Adicionar perfil", icon: UserPlus },
  { id: "property", label: "Adicionar habitação", icon: Home },
  { id: "account", label: "Os meus dados", icon: User },
];

export default function Definicoes() {
  const [section, setSection] = useState(null);
  return (
    <div className="p-6">
      <h1 className="font-display text-3xl text-navy mb-6">
        Definições de conta
      </h1>

      <div className="flex flex-col md:flex-row gap-6">
        {/* Menu esq */}
        <nav className="w-full md:w-64 shrink-0 flex flex-col gap-2">
          {SECTIONS.map((item) => {
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
        </nav>

        {/* Painel dta*/}
        <div className="flex-1 max-w-3xl rounded-3xl bg-card border border-border overflow-hidden max-h-[80vh]">
          <div className="h-full overflow-y-auto p-6">
            {section === null && (
              <p className="text-sm text-muted">Seleciona uma opção.</p>
            )}
            {section === "password" && <PasswordForm />}
            {section === "property" && <PropertyForm />}
            {section === "profile" && <ProfileForm />}
            {section === "account" && <AccountForm />}
          </div>
        </div>
      </div>
    </div>
  );
}
