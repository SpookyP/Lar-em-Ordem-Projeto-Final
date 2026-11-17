import { Link } from "react-router-dom";
import { MapPin, Pencil } from "lucide-react";
import { categoryInfo, proposalStatus } from "../../data/assistanceInfo";
import { formatDate, formatDay } from "../../utils/date";
import { formatAddress, formatMoney } from "../../utils/format";

// one proposal in the provider's list: a table row on large screens (columns come from the page),
// a stacked card on small ones
export function ProviderProposalCard({ proposal, columns, busy, onWithdraw }) {
  const status = proposalStatus(proposal.status, proposal.isExpired);
  const pending = proposal.status === "pending";
  const request = proposal.request;
  const { icon: Icon, color } = categoryInfo(request?.category.slug);

  return (
    <div className={`grid grid-cols-1 items-center gap-3 px-6 py-4 lg:gap-4 ${columns}`}>
      <div className="flex min-w-0 items-center gap-4">
        <span className={`flex size-11 shrink-0 items-center justify-center rounded-xl ${color}`}>
          <Icon size={18} aria-hidden="true" />
        </span>
        <div className="min-w-0">
          {request ? (
            <Link
              to={`/provider/requests/${request.id}`}
              state={{ proposal }}
              className="block truncate font-semibold text-text hover:underline"
            >
              {request.title}
            </Link>
          ) : (
            <p className="truncate font-semibold text-text">Pedido removido</p>
          )}
          {request && (
            <p className="flex items-center gap-1.5 text-sm text-muted">
              <MapPin size={14} className="shrink-0" aria-hidden="true" />
              <span className="truncate">
                {formatAddress(request.address)} · #{request.id.slice(-6).toUpperCase()}
              </span>
            </p>
          )}
        </div>
      </div>

      <p className="font-display text-xl text-navy">{formatMoney(proposal.value)}</p>

      <div className="text-sm">
        <p className="text-text">{formatDay(proposal.availableDate)}</p>
        <p className="text-muted">
          {proposal.availableFrom}–{proposal.availableUntil}
        </p>
      </div>

      <div className="text-sm">
        <p className="text-text">
          {proposal.isExpired ? "Expirou a " : ""}
          {formatDay(proposal.validity)}
        </p>
        <p className="text-muted">enviada {formatDate(proposal.createdAt)}</p>
      </div>

      <span className={`justify-self-start rounded-full px-3 py-1 text-xs font-semibold ${status.style}`}>
        {status.label}
      </span>

      <div className="flex gap-2 lg:justify-end">
        {pending && (
          <>
            <button
              type="button"
              onClick={() => onWithdraw(proposal)}
              disabled={busy}
              className="rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-red transition hover:bg-red-light disabled:opacity-60"
            >
              Retirar
            </button>
            {request && (
              <Link
                to={`/provider/requests/${request.id}/proposal`}
                state={{ proposal }}
                className="flex items-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-light"
              >
                <Pencil size={15} aria-hidden="true" />
                Editar
              </Link>
            )}
          </>
        )}
      </div>
    </div>
  );
}