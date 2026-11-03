import { useAuth } from "../../hooks/useAuth";
import { useState } from "react";
import { Link } from "react-router-dom";
import { Check } from "lucide-react";
import { addProfile } from "../../services/api/accountService";
import { accountTypes } from "../../data/registerFields";
import { ProfileWizard } from "../profile/ProfileWizard";


export function ProfileForm() {
  const { user, refreshUser } = useAuth();
  const [added, setAdded] = useState("");
  const [round, setRound] = useState(0);

  const currentRoles = user?.roles ?? [];
  const available = accountTypes.filter((t) => !currentRoles.includes(t.role));

  async function handleSubmit(payload) {
    await addProfile(payload);
    await refreshUser();
    const label = accountTypes.find((t) => t.role === payload.role)?.label ?? "";
    setAdded(label.toLowerCase());
  }

  if (added) {
    return (
      <div className="flex flex-col items-start gap-4">
        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-green-light text-green">
          <Check size={22} aria-hidden="true" />
        </span>
        <div>
          <h2 className="text-lg font-semibold text-navy">Perfil adicionado</h2>
          <p className="mt-1 text-sm text-muted">
            O perfil de {added} já está na sua conta. Pode escolhê-lo na página
            de perfis.
          </p>
        </div>
        <div className="flex flex-wrap gap-3">
          <Link
            to="/profiles"
            className="rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition hover:bg-navy-dark"
          >
            Ir para os perfis
          </Link>
          {available.length > 0 && (
            <button
              type="button"
              onClick={() => {
                setAdded("");
                setRound((r) => r + 1);
              }}
              className="rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-text transition hover:bg-surface"
            >
              Adicionar outro
            </button>
          )}
        </div>
      </div>
    );
  }

  if (available.length === 0) {
    return <p className="text-sm text-muted">Já tem todos os perfis disponíveis.</p>;
  }

  return (
    <ProfileWizard
      key={round}
      roles={available}
      onSubmit={handleSubmit}
      submitLabel="Adicionar perfil"
      submittingLabel="A adicionar..."
    />
  );
}