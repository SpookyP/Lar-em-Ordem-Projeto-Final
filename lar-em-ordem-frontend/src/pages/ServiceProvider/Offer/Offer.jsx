import { useParams, useNavigate, Link } from "react-router-dom";
import { ChevronLeft, MapPin, Calendar } from "lucide-react";
import { useAssistanceRequest } from "../../../hooks/useAssistanceRequest";
import { createProposal } from "../../../services/api/proposalService";
import { categoryInfo } from "../../../data/assistanceInfo";
import { ProposalForm } from "../../../components/ui/ProposalForm";
import { AttachmentGallery } from "../../../components/ui/AttachmentGallery";
import { formatAddress } from "../../../utils/format";
import { formatDate } from "../../../utils/date";

export default function Offer() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { request, loading, error } = useAssistanceRequest(id);

  async function handleSubmit(data) {
    await createProposal(id, data);
    navigate("/provider/proposals");
  }

  const { icon: Icon, color } = categoryInfo(request?.category.slug);

  return (
    <div className="flex min-h-0 flex-1 flex-col gap-6">
      <div className="flex shrink-0 items-center gap-4">
        <Link
          to="/provider/requests"
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
        <div className="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto pr-2 pb-6">
          {/* Banner com Categoria */}
          <div className="relative flex shrink-0 items-end justify-between overflow-hidden rounded-2xl bg-navy p-8 text-white min-h-35 shadow-sm">
            <div className="absolute right-6 top-1/2 -translate-y-1/2 opacity-10">
              <Icon size={120} />
            </div>
            <span
              className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${color}`}
            >
              <Icon size={14} />
              {request.category.name}
            </span>
          </div>

          {/* Detalhes do Pedido */}
          <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6 shrink-0">
            <div className="flex flex-col gap-2">
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
                  Publicado a {formatDate(request.createdAt)}
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
          </div>

          {/* Formulário para Enviar Proposta */}
          <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6 shrink-0">
            <h2 className="font-display text-xl text-navy">Enviar Proposta</h2>

            {request.status === "open" ? (
              <ProposalForm
                submitLabel="Submeter Proposta"
                onSubmit={handleSubmit}
              />
            ) : (
              <p className="text-sm text-muted">
                Este pedido já não aceita propostas.
              </p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
