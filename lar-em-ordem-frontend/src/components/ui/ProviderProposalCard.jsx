import { Link } from "react-router-dom";
import { CalendarDays, MapPin, Pencil, Trash2 } from "lucide-react";
import { categoryInfo, proposalStatus } from "../../data/assistanceInfo";
import { formatDate, formatDay } from "../../utils/date";
import { formatAddress, formatMoney } from "../../utils/format";

const ICON_BUTTON =
  "flex size-10 items-center justify-center rounded-xl transition disabled:cursor-not-allowed disabled:opacity-40";

const MOBILE_BUTTON =
  "flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-40";

  export function ProviderProposalCard({ proposal, columns, busy, onWithdraw }) {
  const status = proposalStatus(proposal.status, proposal.isExpired);
  const pending = proposal.status === "pending";
  const closed = !pending && proposal.status !== "accepted";
  const request = proposal.request;
  const { icon: Icon, color } = categoryInfo(request?.category.slug);
  const editLink = pending && request ? `/provider/requests/${request.id}/proposal` : null;

  const icon = (
    <span
      className={`flex size-11 shrink-0 items-center justify-center rounded-xl ${color} ${closed ? "opacity-50" : ""}`}
    >
      <Icon size={18} aria-hidden="true" />
    </span>
  );

  const title = request ? (
    <Link
      to={`/provider/requests/${request.id}`}
      className={`font-semibold hover:underline ${closed ? "text-muted" : "text-text"}`}
    >
      {request.title}
    </Link>
  ) : (
    <span className="font-semibold text-muted">Pedido removido</span>
  );

  const badge = (
    <span
      className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold ${status.style}`}
    >
      <span className="size-1.5 rounded-full bg-current" aria-hidden="true" />
      {status.label}
    </span>
  );

  const validity = `${proposal.isExpired ? "Expirou a" : "Válida até"} ${formatDay(proposal.validity)}`;

  return (
    <div>
      <article className="flex flex-col gap-3 p-4 sm:px-6 lg:hidden">
        <div className="flex items-start gap-3">
          {icon}
          <div className="min-w-0 flex-1">
            <p className="line-clamp-2">{title}</p>
            {request && (
              <p className="mt-0.5 flex items-center gap-1.5 text-sm text-muted">
                <MapPin size={14} className="shrink-0" aria-hidden="true" />
                <span className="truncate">{formatAddress(request.address)}</span>
              </p>
            )}
          </div>
          <div className="flex shrink-0 flex-col items-end gap-1.5">
            <p className={`font-display text-xl ${closed ? "text-muted" : "text-navy"}`}>
              {formatMoney(proposal.value)}
            </p>
            {badge}
          </div>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-xl bg-surface px-3.5 py-2.5 text-sm text-muted">
          <span className="flex items-center gap-1.5">
            <CalendarDays size={14} className="shrink-0" aria-hidden="true" />
            {formatDay(proposal.availableDate)} · {proposal.availableFrom}–{proposal.availableUntil}
          </span>
          <span>{validity}</span>
        </div>

        {pending && (
          <div className={`grid gap-2 ${editLink ? "grid-cols-2" : "grid-cols-1"}`}>
            <button
              type="button"
              onClick={() => onWithdraw(proposal)}
              disabled={busy}
              className={`${MOBILE_BUTTON} border border-border bg-card text-red enabled:hover:bg-red-light`}
            >
              <Trash2 size={16} aria-hidden="true" />
              Retirar
            </button>
            {editLink && (
              <Link to={editLink} className={`${MOBILE_BUTTON} bg-navy text-white hover:bg-navy-light`}>
                <Pencil size={16} aria-hidden="true" />
                Editar
              </Link>
            )}
          </div>
        )}
      </article>

      <div className={`hidden items-center gap-4 px-6 py-4 lg:grid ${columns}`}>
        <div className="flex min-w-0 items-center gap-4">
          {icon}
          <div className="min-w-0">
            <p className="truncate">{title}</p>
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

        <span className="justify-self-start">{badge}</span>

        <div className="flex justify-center gap-2">
          <button
            type="button"
            onClick={() => onWithdraw(proposal)}
            disabled={!pending || busy}
            aria-label="Retirar proposta"
            title="Retirar proposta"
            className={`${ICON_BUTTON} border border-border text-red enabled:hover:bg-red-light disabled:text-muted`}
          >
            <Trash2 size={17} />
          </button>
          {editLink ? (
            <Link
              to={editLink}
              aria-label="Editar proposta"
              title="Editar proposta"
              className={`${ICON_BUTTON} bg-navy text-white hover:bg-navy-light`}
            >
              <Pencil size={17} />
            </Link>
          ) : (
            <button
              type="button"
              disabled
              aria-label="Editar proposta"
              title="Esta proposta já não pode ser editada"
              className={`${ICON_BUTTON} border border-border text-muted`}
            >
              <Pencil size={17} />
            </button>
          )}
        </div>
      </div>
    </div>
  );
}