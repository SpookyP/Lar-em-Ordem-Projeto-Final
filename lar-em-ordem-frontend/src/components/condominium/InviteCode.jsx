import { useState } from "react";
import { RefreshCw } from "lucide-react";
import { CopyButton } from "./CopyButton";

/**
 * The code residents type to link their home to the condominium, with a copy button and,
 * when onRegenerate is given, a way to replace it (the old code stops working).
 */
export function InviteCode({ code, onRegenerate }) {
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);

  async function regenerate() {
    setBusy(true);
    try {
      await onRegenerate();
      setConfirming(false);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="flex flex-col gap-3 rounded-xl bg-surface px-4 py-3">
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-xs text-muted">Código de convite</p>
          <p className="font-mono text-lg tracking-widest text-navy">{code}</p>
        </div>
        <div className="flex items-center gap-2">
          <CopyButton text={code} />
          {onRegenerate && !confirming && (
            <button
              type="button"
              onClick={() => setConfirming(true)}
              className="flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-sm text-muted transition hover:text-navy"
            >
              <RefreshCw size={15} />
              Gerar novo
            </button>
          )}
        </div>
      </div>

      {confirming && (
        <div className="flex flex-col gap-2 border-t border-border pt-3 text-sm">
          <p className="text-muted">
            O código atual deixa de funcionar. As casas que já entraram continuam ligadas.
          </p>
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={regenerate}
              disabled={busy}
              className="rounded-full bg-navy px-3 py-1 text-white transition hover:bg-navy-light disabled:opacity-60"
            >
              {busy ? "A gerar..." : "Gerar novo código"}
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
      )}
    </div>
  );
}