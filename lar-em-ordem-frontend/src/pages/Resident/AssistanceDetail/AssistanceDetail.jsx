import { useState } from "react";
import { useParams, Link } from "react-router-dom";
import { ChevronLeft, MapPin, Calendar } from "lucide-react";
import { useAssistanceRequest } from "../../../hooks/useAssistanceRequest";
import {
  acceptProposal,
  rejectProposal,
} from "../../../services/api/proposalService";
import { cancelAssistanceRequest } from "../../../services/api/assistanceService";
import { categoryInfo } from "../../../data/assistanceInfo";
import { ProposalCard } from "../../../components/ui/ProposalCard";
import { AttachmentGallery } from "../../../components/ui/AttachmentGallery";
import { formatAddress } from "../../../utils/format";
import { formatDate } from "../../../utils/date";
import { getErrorMessage } from "../../../utils/apiError";

export default function AssistanceDetail() {
  const { id } = useParams();
  const { request, loading, error, reload } = useAssistanceRequest(id);
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState("");
  const [confirmingCancel, setConfirmingCancel] = useState(false);

  async function run(action) {
    setBusy(true);
    setActionError("");
    try {
      await action();
      reload();
    } catch (err) {
      setActionError(getErrorMessage(err));
    } finally {
      setBusy(false);
      setConfirmingCancel(false);
    }
  }

  const handleAccept = (proposal) => run(() => acceptProposal(proposal.id));
  const handleReject = (proposal) => run(() => rejectProposal(proposal.id));
  const handleCancel = () => run(() => cancelAssistanceRequest(request.id));

  const open = request?.status === "open";
  const { icon: Icon, color } = categoryInfo(request?.category.slug);

  return (
    <div className="flex flex-col gap-6 max-w-3xl mx-auto">
      <div className="flex items-center gap-4">
        <Link
          to="/assistance"
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card shadow-sm text-muted transition hover:text-navy"
          aria-label="Voltar"
        >
          <ChevronLeft size={20} />
        </Link>
        <div>
          <h1 className="font-display text-2xl text-navy">Detalhe do Pedido</h1>
          {request && (
            <p className="text-sm text-muted">
              #{request.id.slice(-6).toUpperCase()} · Publicado a{" "}
              {formatDate(request.createdAt)}
            </p>
          )}
        </div>
      </div>

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : error && !request ? (
        <p className="text-sm text-red">{error}</p>
      ) : (
        <>
          <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6">
            <div className="flex flex-col gap-3">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span
                  className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${color}`}
                >
                  <Icon size={14} />
                  {request.category.name}
                </span>
                <span className="rounded-md bg-orange-light px-2.5 py-1 text-[11px] font-medium text-orange">
                  {request.statusLabel}
                </span>
              </div>

              <h2 className="font-display text-xl text-navy">
                {request.title}
              </h2>

              <div className="flex flex-wrap items-center gap-4 text-sm text-muted">
                <span className="flex items-center gap-1.5">
                  <MapPin size={16} />
                  {formatAddress(request.address)}
                </span>
                <span className="flex items-center gap-1.5">
                  <Calendar size={16} />
                  {formatDate(request.createdAt)}
                </span>
              </div>
            </div>

            <div className="flex flex-col gap-2 border-t border-surface pt-4">
              <h3 className="text-sm font-semibold text-muted">Descrição</h3>
              <p className="text-sm text-navy leading-relaxed whitespace-pre-line wrap-break-word">
                {request.description}
              </p>
            </div>

            <AttachmentGallery attachments={request.attachments} />

            {open && (
              <div className="flex flex-wrap items-center gap-3 border-t border-surface pt-4 text-sm">
                {confirmingCancel ? (
                  <>
                    <span className="text-muted">
                      As propostas pendentes serão rejeitadas.
                    </span>
                    <button
                      onClick={handleCancel}
                      disabled={busy}
                      className="rounded-full bg-red px-4 py-1.5 text-white transition hover:opacity-90 disabled:opacity-60"
                    >
                      Cancelar pedido
                    </button>
                    <button
                      onClick={() => setConfirmingCancel(false)}
                      className="text-muted underline hover:text-navy"
                    >
                      Voltar
                    </button>
                  </>
                ) : (
                  <button
                    onClick={() => setConfirmingCancel(true)}
                    disabled={busy}
                    className="text-muted underline hover:text-red disabled:opacity-60"
                  >
                    Cancelar pedido
                  </button>
                )}
              </div>
            )}
          </div>

          <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
            <div className="flex items-center justify-between">
              <h2 className="font-display text-xl text-navy">
                Propostas recebidas
              </h2>
              <span className="text-xs text-muted">
                Total: {request.proposals.length}
              </span>
            </div>

            {(actionError || error) && (
              <p className="text-sm text-red font-medium">
                {actionError || error}
              </p>
            )}

            {request.proposals.length === 0 ? (
              <p className="text-sm text-muted">
                Ainda não recebeu propostas para este pedido.
              </p>
            ) : (
              <div className="flex flex-col gap-3">
                {request.proposals.map((proposal) => (
                  <ProposalCard
                    key={proposal.id}
                    proposal={proposal}
                    canRespond={open}
                    busy={busy}
                    onAccept={handleAccept}
                    onReject={handleReject}
                  />
                ))}
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}
