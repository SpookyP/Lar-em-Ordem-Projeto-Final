import { useState } from "react";
import { Link } from "react-router-dom";
import { CalendarDays, MapPin } from "lucide-react";
import { categoryInfo, proposalStatus } from "../../data/assistanceInfo";
import { formatDay } from "../../utils/date";
import { formatAddress, formatMoney } from "../../utils/format";

//proposta vista pelo prestador
export function ProviderProposalCard({ proposal, busy, onEdit, onWithdraw }) {
  const [confirming, setConfirming] = useState(false);
  const status = proposalStatus(proposal.status);
  const pending = proposal.status === "pending";
  const request = proposal.request;
  const { icon: Icon, color } = categoryInfo(request?.category.slug);

  return (
    <div className="flex flex-col sm:flex-row rounded-xl bg-surface border border-border/50 overflow-hidden">
      {/* corpo do ticket */}
      <div className="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
        <div
          className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${color}`}
        >
          <Icon size={16} />
        </div>

        <div className="flex min-w-0 flex-col">
          <h3 className="truncate font-display text-sm text-navy">
            {request ? (
              <Link
                to={`/prestador/pedidos/${request.id}`}
                className="hover:underline"
              >
                {request.title}
              </Link>
            ) : (
              "Pedido"
            )}
          </h3>
          <div className="flex flex-wrap items-center gap-x-3 text-[11px] text-muted mt-0.5">
            <span className="font-semibold text-navy">
              {formatMoney(proposal.value)}
            </span>
            {request && (
              <span className="flex items-center gap-1">
                <MapPin size={11} />
                {formatAddress(request.address)}
              </span>
            )}
            <span className="flex items-center gap-1">
              <CalendarDays size={11} />
              {formatDay(proposal.availableDate)}, {proposal.availableFrom}–
              {proposal.availableUntil}
            </span>
          </div>
          {pending && (
            <span className="text-[11px] text-muted">
              Válida até {formatDay(proposal.validity)}
            </span>
          )}
        </div>
      </div>

      {/* talao */}
      <div className="flex flex-wrap items-center justify-between gap-3 border-t sm:border-t-0 sm:border-l border-dashed border-navy/20 px-4 py-3 sm:w-64 shrink-0">
        <span
          className={`font-medium text-[11px] px-2.5 py-1 rounded-md whitespace-nowrap ${status.style}`}
        >
          {status.label}
        </span>

        {pending &&
          (confirming ? (
            <div className="flex items-center gap-2 text-xs">
              <button
                onClick={() => onWithdraw(proposal)}
                disabled={busy}
                className="rounded-full bg-red px-3 py-1.5 text-white transition hover:opacity-90 disabled:opacity-60"
              >
                Retirar
              </button>
              <button
                onClick={() => setConfirming(false)}
                className="text-muted underline hover:text-navy"
              >
                Voltar
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-3 text-xs">
              <button
                onClick={() => onEdit(proposal)}
                disabled={busy}
                className="rounded-full bg-navy px-3.5 py-1.5 text-white transition hover:opacity-90 disabled:opacity-60"
              >
                Editar
              </button>
              <button
                onClick={() => setConfirming(true)}
                disabled={busy}
                className="text-muted underline hover:text-red disabled:opacity-60"
              >
                Retirar
              </button>
            </div>
          ))}
      </div>
    </div>
  );
}