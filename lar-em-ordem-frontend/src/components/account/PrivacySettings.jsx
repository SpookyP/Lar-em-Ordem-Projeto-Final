import { useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { downloadMyData, updatePrivacy } from "../../services/api/accountService";
import { Toggle } from "../form";
import { getErrorMessage } from "../../utils/apiError";
import { formatDate } from "../../utils/date";

// Consents come with the user (GET /api/user), so this screen asks the API nothing until something changes.
export function PrivacySettings() {
  const { user, patchUser } = useAuth();
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState("");

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState("");

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
      setError(getErrorMessage(err, "Não foi possível guardar a sua escolha. Tente novamente."));
    } finally {
      setSaving(false);
    }
  }

  async function handleDownload() {
    setDownloading(true);
    setDownloadError("");

    try {
      const blob = await downloadMyData();
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `lar-em-ordem-dados-${new Date().toISOString().slice(0, 10)}.json`;
      link.click();
      // give the browser a moment to start the download before freeing the file
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (err) {
      setDownloadError(getErrorMessage(err, "Escolha como usamos os seus dados."));
    } finally {
      setDownloading(false);
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h2 className="font-medium text-navy">Privacidade</h2>
        <p className="mt-1 text-sm text-muted">Escolha como usamos os seus dados.</p>
      </div>

      <Toggle
        checked={sharing}
        onChange={handleSharingChange}
        disabled={saving}
        label="Partilhar os meus consumos"
        description="Os seus consumos entram, de forma anónima, nas médias de casas semelhantes. Se desligar, deixam de contar a partir do próximo cálculo."
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
            Aceitou a{" "}
            <Link to="/privacy" className="font-medium text-navy underline">
              Política de Privacidade
            </Link>{" "}
            (versão {policy.version}) em {formatDate(policy.at)}.
          </>
        ) : (
          <>
            Consulte a{" "}
            <Link to="/privacy" className="font-medium text-navy underline">
              Política de Privacidade
            </Link>
            .
                    </>
        )}
      </p>

      <div className="flex flex-col gap-2 rounded-xl border border-border p-4">
        <p className="text-sm font-medium text-navy">Os seus dados</p>
        <p className="text-sm text-muted">
          Descarregue uma cópia de tudo o que guardamos sobre si (conta, casas, faturas, documentos, pedidos e
          consentimentos), num ficheiro JSON.
        </p>
        <button
          type="button"
          onClick={handleDownload}
          disabled={downloading}
          className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
        >
          {downloading ? "A preparar..." : "Descarregar os meus dados"}
        </button>
        {downloadError && (
          <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {downloadError}
          </p>
        )}
      </div>
    </div>
  );
}