import { useState } from "react";
import { proposalStatus } from "../../data/assistanceInfo";
import { formatDay } from "../../utils/date";
import { formatMoney } from "../../utils/format";

//proposta vista pelo morador
export function ProposalCard({ proposal, canRespond, busy, onAccept, onReject }) {
  const [confirming, setConfirming] = useState(false);
  const status = proposalStatus(proposal.status);
  const pending = proposal.status === "pending";
  const provider = proposal.provider;

  return (
    <div className="flex flex-col gap-3 rounded-xl bg-surface border border-border/50 p-4">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="flex flex-col">
          <h3 className="font-display text-base text-navy">
            {provider?.companyName ?? "Prestador"}
          </h3>
          <span className="text-lg font-semibold text-navy">
            {formatMoney(proposal.value)}
          </span>
        </div>
        <span
          className={`font-medium text-[11px] px-2.5 py-1 rounded-md whitespace-nowrap ${status.style}`}
        >
          {status.label}
        </span>
      </div>

      <p className="text-sm text-navy whitespace-pre-line break-words">
        {proposal.description}
      </p>

      <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
        <span>
          Disponível a {formatDay(proposal.availableDate)}, das{" "}
          {proposal.availableFrom} às {proposal.availableUntil}
        </span>
        <span>Válida até {formatDay(proposal.validity)}</span>
      </div>

      {/* contactos so depois de aceitar */}
      {proposal.status === "accepted" && provider && (
        <div className="flex flex-wrap gap-x-4 gap-y-1 rounded-lg bg-green-light px-3 py-2 text-xs text-green">
          {provider.phone && <span>Telefone: {provider.phone}</span>}
          {provider.email && <span>Email: {provider.email}</span>}
        </div>
      )}

      {pending && canRespond && (
        <div className="flex flex-wrap items-center gap-3 text-xs">
          {confirming ? (
            <>
              <span className="text-muted">
                As restantes propostas serão rejeitadas.
              </span>
              <button
                onClick={() => onAccept(proposal)}
                disabled={busy}
                className="rounded-full bg-navy px-3.5 py-1.5 text-white transition hover:opacity-90 disabled:opacity-60"
              >
                Confirmar
              </button>
              <button
                onClick={() => setConfirming(false)}
                className="text-muted underline hover:text-navy"
              >
                Voltar
              </button>
            </>
          ) : (
            <>
              <button
                onClick={() => setConfirming(true)}
                disabled={busy}
                className="rounded-full bg-navy px-3.5 py-1.5 text-white transition hover:opacity-90 disabled:opacity-60"
              >
                Aceitar
              </button>
              <button
                onClick={() => onReject(proposal)}
                disabled={busy}
                className="text-muted underline hover:text-red disabled:opacity-60"
              >
                Rejeitar
              </button>
            </>
          )}
        </div>
      )}
    </div>
  );
}