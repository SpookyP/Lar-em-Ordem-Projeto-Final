import { Link, useNavigate, useParams } from "react-router-dom";
import { ArrowLeft, MapPin } from "lucide-react";
import { useAssistanceRequest } from "../../../hooks/useAssistanceRequest";
import {
  createProposal,
  updateProposal,
} from "../../../services/api/proposalService";
import { categoryInfo, proposalStatus } from "../../../data/assistanceInfo";
import { ProposalForm } from "../../../components/assistance/ProposalForm";
import { formatAddress } from "../../../utils/format";
import { formatDate, formatDay } from "../../../utils/date";
import { useGoBack } from "../../../hooks/useGoBack";
import { LoadingState } from "../../../components/ui/LoadingState";

function RequestSummary({ request, proposal }) {
  const { icon: Icon, color } = categoryInfo(request.category.slug);
  const status =
    proposal && proposalStatus(proposal.status, proposal.isExpired);

  return (
    <aside className="flex flex-col gap-5 rounded-2xl border border-border bg-card p-7 shadow-sm">
      <div className="flex items-start gap-4">
        <span
          className={`flex size-12 shrink-0 items-center justify-center rounded-xl ${color}`}
        >
          <Icon size={20} aria-hidden="true" />
        </span>
        <div className="min-w-0">
          <p className="text-sm text-muted">{request.category.name}</p>
          <h2 className="font-display text-2xl text-navy">{request.title}</h2>
        </div>
      </div>

      <p className="flex items-center gap-2 text-sm text-muted">
        <MapPin size={16} className="shrink-0" aria-hidden="true" />
        {formatAddress(request.address)}
      </p>

      {proposal && (
        <>
          <div className="flex items-center justify-between gap-3 rounded-xl bg-surface px-5 py-4">
            <span className="text-sm text-muted">Estado atual</span>
            <span
              className={`rounded-full px-3 py-1 text-xs font-semibold ${status.style}`}
            >
              {status.label}
            </span>
          </div>

          <ol className="flex flex-col gap-5 border-l-2 border-border pl-5">
            <li className="relative">
              <span className="absolute -left-6.75 top-1 size-3 rounded-full bg-teal ring-4 ring-teal-light" />
              <p className="text-sm font-medium text-text">Proposta enviada</p>
              <p className="text-xs text-muted">
                {formatDate(proposal.createdAt)}
              </p>
            </li>
            <li className="relative">
              <span className="absolute -left-6.75 top-1 size-3 rounded-full bg-border" />
              <p className="text-sm font-medium text-text">
                A aguardar resposta do cliente
              </p>
              <p className="text-xs text-muted">
                Válida até {formatDay(proposal.validity)}
              </p>
            </li>
          </ol>
        </>
      )}

      <Link
        to={`/provider/requests/${request.id}`}
        className="flex items-center justify-center gap-2 rounded-xl border border-border px-5 py-3 font-semibold text-navy transition hover:bg-surface"
      >
        <ArrowLeft size={17} aria-hidden="true" />
        Ver pedido original
      </Link>
    </aside>
  );
}

export default function ProposalFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { request, loading, error } = useAssistanceRequest(id);

  // the API sends the provider's own proposal with the request: edit it if there is one
  const proposal = request?.myProposal ?? null;
  const isEdit = Boolean(proposal);
  const canEdit = isEdit
    ? proposal.status === "pending"
    : request?.status === "open";

  const goBack = useGoBack("/provider/requests");

  async function handleSubmit(data) {
    if (isEdit) await updateProposal(proposal.id, data);
    else await createProposal(id, data);
    navigate("/provider/proposals");
  }

  return (
    <div className="flex flex-col gap-6">
      <nav
        aria-label="Caminho"
        className="flex items-center gap-2 text-sm text-muted"
      >
        <Link
          to={isEdit ? "/provider/proposals" : "/provider/requests"}
          className="flex items-center gap-2 transition hover:text-navy"
        >
          <ArrowLeft size={16} aria-hidden="true" />
          {isEdit ? "As minhas propostas" : "Pedidos disponíveis"}
        </Link>
        <span aria-hidden="true">/</span>
        <span className="font-medium text-text">
          #{id.slice(-6).toUpperCase()}
        </span>
      </nav>

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
        <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
          <RequestSummary request={request} proposal={proposal} />

          <section className="flex min-w-0 flex-col gap-6 rounded-2xl border border-border bg-card p-8 shadow-sm">
            <div>
              <h1 className="font-display text-3xl text-navy">
                {isEdit ? "Editar proposta" : "Nova proposta"}
              </h1>
              <p className="mt-1 text-sm text-muted">
                {isEdit
                  ? "Pode alterar a proposta enquanto o cliente não responder."
                  : "O cliente vê a proposta no pedido e pode aceitá-la ou recusá-la."}
              </p>
            </div>

            {canEdit ? (
              <ProposalForm
                proposal={proposal}
                submitLabel={isEdit ? "Guardar alterações" : "Enviar proposta"}
                onSubmit={handleSubmit}
                onCancel={() => goBack()}
              />
            ) : (
              <p className="text-sm text-muted">
                {isEdit
                  ? "Esta proposta já não pode ser alterada."
                  : "Este pedido já não aceita propostas."}
              </p>
            )}
          </section>
        </div>
      )}
    </div>
  );
}
