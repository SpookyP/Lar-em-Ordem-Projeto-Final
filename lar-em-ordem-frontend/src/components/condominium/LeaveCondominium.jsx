import { useState } from "react";
import { removeProperty } from "../../services/api/condominiumService";
import { getErrorMessage } from "../../utils/apiError";

/** Unlink the home from its condominium, after a confirmation. */
export function LeaveCondominium({ condominium, propertyId, onLeft }) {
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function leave() {
    setBusy(true);
    setError("");
    try {
      await removeProperty(condominium.id, propertyId);
      onLeft();
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível sair do condomínio."));
      setBusy(false);
    }
  }

  return (
    <div className="flex flex-col gap-2 text-sm">
      {error && (
        <p role="alert" className="text-red">
          {error}
        </p>
      )}
      {confirming ? (
        <div className="flex flex-col gap-2 rounded-xl bg-surface p-3">
          <p className="text-muted">
            A tua casa deixa de ver os avisos e os documentos de {condominium.name}. Podes voltar a
            entrar com o código de convite.
          </p>
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={leave}
              disabled={busy}
              className="rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
            >
              {busy ? "A sair..." : "Sim, sair"}
            </button>
            <button
              type="button"
              onClick={() => setConfirming(false)}
              className="text-muted underline hover:text-navy"
            >
              Cancelar
            </button>
          </div>
        </div>
      ) : (
        <button
          type="button"
          onClick={() => setConfirming(true)}
          className="self-start text-red hover:opacity-80"
        >
          Sair do condomínio
        </button>
      )}
    </div>
  );
}