import { useState } from "react";
import { cancelTransfer, startTransfer } from "../../services/api/condominiumService";
import { CopyButton } from "./CopyButton";
import { formatDate } from "../../utils/date";
import { getErrorMessage } from "../../utils/apiError";

/**
 * Hand the condominium over to another administrator: a 12-character code, valid for 7 days,
 * shown only once. A pending transfer can be cancelled or replaced by a new code.
 */
export function TransferCondominium({ condominiumId, expiresAt, onChange }) {
  // the code exists only in this answer; after a reload only the expiry date is known
  const [code, setCode] = useState(null);
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const pending = Boolean(expiresAt) && new Date(expiresAt) > new Date();

  async function generate() {
    setBusy(true);
    setError("");
    try {
      const result = await startTransfer(condominiumId);
      setCode(result.code);
      setConfirming(false);
      onChange(result.expiresAt);
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível gerar o código de transferência."));
    } finally {
      setBusy(false);
    }
  }

  async function cancel() {
    setBusy(true);
    setError("");
    try {
      await cancelTransfer(condominiumId);
      setCode(null);
      onChange(null);
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível cancelar a transferência."));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="flex flex-col gap-3 text-sm">
      {error && (
        <p role="alert" className="text-red">
          {error}
        </p>
      )}

      {code && (
        <div className="flex flex-col gap-2 rounded-xl bg-surface px-4 py-3">
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs text-muted">Código de transferência</p>
              <p className="font-mono text-lg tracking-widest text-navy">{code}</p>
            </div>
            <CopyButton text={code} />
          </div>
          <p className="text-xs text-muted">
            Entrega-o ao novo administrador. Este código não volta a ser mostrado.
          </p>
        </div>
      )}

      {pending && (
        <p className="text-muted">
          Transferência pendente até {formatDate(expiresAt)}.
          {!code && " O código já não pode ser mostrado: gera um novo se o perdeste."}
        </p>
      )}

      {confirming ? (
        <div className="flex flex-col gap-2 rounded-xl bg-surface p-3">
          <p className="text-muted">
            Quem usar o código passa a gerir este condomínio, com os avisos, os documentos e as
            frações. Tu deixas de ter acesso.
            {pending && " O código anterior deixa de funcionar."}
          </p>
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={generate}
              disabled={busy}
              className="rounded-full bg-navy px-3 py-1 text-white transition hover:bg-navy-light disabled:opacity-60"
            >
              {busy ? "A gerar..." : "Gerar código"}
            </button>
            <button
              type="button"
              onClick={() => setConfirming(false)}
              className="text-muted underline hover:text-navy"
            >
              Voltar
            </button>
          </div>
        </div>
      ) : (
        <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            onClick={() => setConfirming(true)}
            className="rounded-xl border border-border px-4 py-2 font-medium text-navy transition hover:bg-surface"
          >
            {pending ? "Gerar novo código" : "Transferir para outro administrador"}
          </button>
          {pending && (
            <button
              type="button"
              onClick={cancel}
              disabled={busy}
              className="text-red hover:opacity-80 disabled:opacity-60"
            >
              {busy ? "A cancelar..." : "Cancelar transferência"}
            </button>
          )}
        </div>
      )}
    </div>
  );
}