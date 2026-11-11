import { useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { updatePrivacy } from "../../services/api/accountService";
import { Toggle } from "../form";
import { getErrorMessage } from "../../utils/apiError";
import { formatDate } from "../../utils/date";

// Consents come with the user (GET /api/user), so this screen asks the API nothing until something changes.
export function PrivacySettings() {
  const { user, patchUser } = useAuth();
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState("");

  const consents = user?.privacy?.consents ?? {};
  const policy = consents.privacy_policy;
  const sharing = consents.data_sharing?.granted ?? false;

  async function handleSharingChange(next) {
    setSaving(true);
    setSaved(false);
    setError("");

    try {
      const privacy = await updatePrivacy({ data_sharing: next });
      patchUser({ privacy });
      setSaved(true);
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível guardar a tua escolha. Tenta novamente."));
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h2 className="font-medium text-navy">Privacidade</h2>
        <p className="mt-1 text-sm text-muted">Escolhe como usamos os teus dados.</p>
      </div>

      <Toggle
        checked={sharing}
        onChange={handleSharingChange}
        disabled={saving}
        label="Partilhar os meus consumos"
        description="Os teus consumos entram, de forma anónima, nas médias de casas semelhantes. Se desligares, deixam de contar a partir do próximo cálculo."
      />

      {saved && (
        <p role="status" className="text-sm text-green">
          Escolha guardada.
        </p>
      )}
      {error && (
        <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {error}
        </p>
      )}

      <p className="rounded-xl border border-border p-4 text-sm text-muted">
        {policy ? (
          <>
            Aceitaste a{" "}
            <Link to="/privacy" className="font-medium text-navy underline">
              Política de Privacidade
            </Link>{" "}
            (versão {policy.version}) em {formatDate(policy.at)}.
          </>
        ) : (
          <>
            Consulta a{" "}
            <Link to="/privacy" className="font-medium text-navy underline">
              Política de Privacidade
            </Link>
            .
          </>
        )}
      </p>
    </div>
  );
}