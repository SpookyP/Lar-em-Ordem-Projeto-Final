import { Link, useParams } from "react-router-dom";
import { ArrowLeft, CalendarDays, MapPin, Pencil, Send } from "lucide-react";
import { useAssistanceRequest } from "../../../hooks/useAssistanceRequest";
import {
  categoryInfo,
  proposalStatus,
  providerRequestLabel,
} from "../../../data/assistanceInfo";
import { AttachmentGallery } from "../../../components/assistance/AttachmentGallery";
import { formatAddress, formatMoney } from "../../../utils/format";
import { formatDate, formatDay } from "../../../utils/date";
import { LoadingState } from "../../../components/ui/LoadingState";

function Tile({ icon: Icon, label, value }) {
  return (
    <div className="flex min-w-0 items-center gap-3 rounded-xl bg-surface px-4 py-3">
      <Icon size={17} className="shrink-0 text-muted" aria-hidden="true" />
      <div className="min-w-0">
        <p className="text-xs text-muted">{label}</p>
        <p className="truncate text-sm font-semibold text-text">{value}</p>
      </div>
    </div>
  );
}

function Row({ label, value }) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-border py-3 text-sm last:border-b-0">
      <span className="text-muted">{label}</span>
      <span className="text-right font-medium text-text">{value}</span>
    </div>
  );
}

function MyProposal({ proposal, requestId }) {
  const status = proposalStatus(proposal.status, proposal.isExpired);

  return (
    <section className="flex flex-col gap-5 rounded-2xl border border-border bg-card p-7 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <h2 className="font-display text-2xl text-navy">A sua proposta</h2>
        <span
          className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${status.style}`}
        >
          {status.label}
        </span>
      </div>

      <p className="font-display text-4xl text-navy">
        {formatMoney(proposal.value)}
      </p>

      <div>
        <Row
          label="Disponibilidade"
          value={`${formatDay(proposal.availableDate)} · ${proposal.availableFrom}–${proposal.availableUntil}`}
        />
        <Row label="Válida até" value={formatDay(proposal.validity)} />
        <Row label="Enviada a" value={formatDate(proposal.createdAt)} />
      </div>

      {proposal.description && (
        <p className="whitespace-pre-line rounded-xl bg-surface px-4 py-3 text-sm leading-relaxed text-text">
          {proposal.description}
        </p>
      )}

      {proposal.status === "pending" && (
        <Link
          to={`/provider/requests/${requestId}/proposal`}
          className="flex items-center justify-center gap-2 rounded-xl bg-navy px-5 py-3.5 font-semibold text-white transition hover:bg-navy-light"
        >
          <Pencil size={17} aria-hidden="true" />
          Editar proposta
        </Link>
      )}
    </section>
  );
}

function NoProposal({ request }) {
  const open = request.status === "open";

  return (
    <section className="flex flex-col gap-4 rounded-2xl border border-border bg-card p-7 shadow-sm">
      <h2 className="font-display text-2xl text-navy">A sua proposta</h2>
      <p className="text-sm text-muted">
        {open
          ? "Ainda não enviou proposta para este pedido. Indique o preço, quando pode ir e uma nota para o cliente."
          : "Este pedido já não aceita propostas."}
      </p>
      {open && (
        <Link
          to={`/provider/requests/${request.id}/proposal`}
          className="flex items-center justify-center gap-2 rounded-xl bg-navy px-5 py-3.5 font-semibold text-white transition hover:bg-navy-light"
        >
          <Send size={17} aria-hidden="true" />
          Fazer proposta
        </Link>
      )}
    </section>
  );
}

export default function Offer() {
  const { id } = useParams();
  const { request, loading, error } = useAssistanceRequest(id);
  const { icon: Icon, color } = categoryInfo(request?.category.slug);

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-6">
      <div className="flex items-center gap-4">
        <Link
          to="/provider/requests"
          aria-label="Voltar aos pedidos"
          className="flex size-11 shrink-0 items-center justify-center rounded-full bg-card text-muted shadow-sm transition hover:text-navy"
        >
          <ArrowLeft size={20} />
        </Link>
        <div className="min-w-0">
          {request && (
            <p className="text-sm text-muted">
              #{request.id.slice(-6).toUpperCase()} · Publicado a{" "}
              {formatDate(request.createdAt)}
            </p>
          )}
          <h1 className="font-display text-3xl text-navy">Detalhe do pedido</h1>
        </div>
      </div>

      {loading ? (
        <LoadingState />
      ) : error && !request ? (
        <p
          role="alert"
          className="rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red"
        >
          {error}
        </p>
      ) : (
        <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
          <article className="min-w-0 overflow-hidden rounded-2xl border border-border bg-card shadow-sm lg:col-span-2">
            <header className="flex flex-col gap-4 bg-navy px-8 py-7 text-white">
              <div className="flex flex-wrap items-center gap-2">
                <span
                  className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${color}`}
                >
                  <Icon size={14} aria-hidden="true" />
                  {request.category.name}
                </span>
                <span className="rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
                  {providerRequestLabel(request.status)}
                </span>
              </div>
              <h2 className="font-display text-3xl">{request.title}</h2>
            </header>

            <div className="grid grid-cols-1 gap-3 border-b border-border p-6 sm:grid-cols-2">
              <Tile
                icon={MapPin}
                label="Localização"
                value={formatAddress(request.address)}
              />
              <Tile
                icon={CalendarDays}
                label="Publicado"
                value={formatDate(request.createdAt)}
              />
            </div>

            <div className="flex flex-col gap-4 p-8">
              <div className="flex flex-col gap-2">
                <h3 className="text-xs font-semibold uppercase tracking-wider text-muted">
                  Descrição
                </h3>
                <p className="whitespace-pre-line wrap-break-word leading-relaxed text-text">
                  {request.description ||
                    "A descrição deste pedido foi removida."}
                </p>
              </div>

              <AttachmentGallery attachments={request.attachments} />
            </div>
          </article>

          <div className="min-w-0">
            {request.myProposal ? (
              <MyProposal
                proposal={request.myProposal}
                requestId={request.id}
              />
            ) : (
              <NoProposal request={request} />
            )}
          </div>
        </div>
      )}
    </div>
  );
}
