import { useState } from "react";
import { Link } from "react-router-dom";
import { Plus, Pencil, Trash2, Eye, EyeOff } from "lucide-react";
import {
  deleteOffer,
  setOfferActive,
} from "../../../services/api/offerService";
import { formatDate } from "../../../utils/date";
import { getOfferStatus } from "../../../utils/offerStatus";
import { Pager } from "../../../components/ui/Pager";
import { useMyOffersPage } from "../../../hooks/useMyOffersPage";
import { ConfirmDialog } from "../../../components/ui/ConfirmDialog";
import { LoadingState } from "../../../components/ui/LoadingState";
import { EmptyState } from "../../../components/ui/EmptyState";

const FILTERS = [
  { value: "", label: "Todas" },
  { value: "current", label: "Em vigor" },
  { value: "scheduled", label: "Agendadas" },
  { value: "expired", label: "Expiradas" },
  { value: "inactive", label: "Inativas" },
];

export default function MyOffers() {
  const [busyId, setBusyId] = useState(null);
  const [deletingOffer, setDeletingOffer] = useState(null);
  const [status, setStatus] = useState("");
  const [actionError, setActionError] = useState("");

  const {
    items: offers,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useMyOffersPage({ perPage: 8, status });

  async function handleToggle(offer) {
    setBusyId(offer.id);
    setActionError("");
    try {
      await setOfferActive(offer.id, !offer.active);
      reload();
    } catch {
      setActionError("Não foi possível atualizar a oferta.");
    } finally {
      setBusyId(null);
    }
  }

  async function confirmDelete() {
    if (!deletingOffer) return;
    const offer = deletingOffer;
    setBusyId(offer.id);
    setActionError("");
    try {
      await deleteOffer(offer.id);
      setDeletingOffer(null);
      if (offers.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        reload();
      }
    } catch {
      setActionError("Não foi possível eliminar a oferta.");
    } finally {
      setBusyId(null);
    }
  }

  const isEmptyAll = !loading && status === "" && total === 0;

  return (
    <div className="flex flex-col gap-6 max-w-5xl mx-auto">
      <div className="flex items-center justify-between">
        <h1 className="font-display text-2xl text-navy">As minhas ofertas</h1>
        <Link
          to="/partner/offers/new"
          className="flex items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light"
        >
          <Plus size={18} />
          Nova oferta
        </Link>
      </div>

      {!isEmptyAll && (
        <div className="flex flex-wrap gap-2">
          {FILTERS.map((f) => (
            <button
              key={f.label}
              onClick={() => setStatus(f.value)}
              className={`rounded-full px-3 py-1.5 text-sm font-medium transition ${
                status === f.value
                  ? "bg-navy text-white"
                  : "bg-card text-muted hover:text-navy"
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      )}

      {(error || actionError) && (
        <p className="text-sm text-red">{error || actionError}</p>
      )}

      {loading && offers.length === 0 ? (
        <LoadingState />
      ) : isEmptyAll ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <EmptyState message="Ainda não tens ofertas." />
          <Link
            to="/partner/offers/new"
            className="mt-3 inline-block font-semibold text-navy hover:underline"
          >
            Criar a primeira oferta
          </Link>
        </div>
      ) : offers.length === 0 ? (
        <EmptyState message="Nenhuma oferta neste estado." />
      ) : (
        <>
          <div className="grid gap-4 lg:grid-cols-2">
            {offers.map((offer) => {
              const st = getOfferStatus(offer);
              return (
                <div
                  key={offer.id}
                  className="rounded-2xl bg-card p-5 shadow-sm flex flex-col gap-3"
                >
                  <div className="flex items-center gap-2">
                    <Link
                      to={`/partner/offers/${offer.id}`}
                      className="hover:underline"
                    >
                      <h2 className="font-display text-lg text-navy">
                        {offer.title}
                      </h2>
                    </Link>
                    <span
                      className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${st.className}`}
                    >
                      {st.label}
                    </span>
                  </div>

                  <p className="text-sm text-muted">{offer.category?.name}</p>
                  <p className="text-sm text-text">{offer.description}</p>
                  <p className="text-xs text-muted">
                    {formatDate(offer.startDate)}
                    {offer.endDate
                      ? ` — ${formatDate(offer.endDate)}`
                      : " — sem fim"}
                  </p>

                  <div className="mt-auto flex items-center gap-4 border-t border-border pt-3 text-sm">
                    <Link
                      to={`/partner/offers/${offer.id}/edit`}
                      className="flex items-center gap-1.5 text-muted hover:text-navy"
                    >
                      <Pencil size={15} /> Editar
                    </Link>
                    <button
                      onClick={() => handleToggle(offer)}
                      disabled={busyId === offer.id}
                      className="flex items-center gap-1.5 text-muted hover:text-navy disabled:opacity-50"
                    >
                      {offer.active ? (
                        <>
                          <Eye size={15} /> Inativar
                        </>
                      ) : (
                        <>
                          <EyeOff size={15} /> Ativar
                        </>
                      )}
                    </button>
                    <button
                      onClick={() => setDeletingOffer(offer)}
                      className="ml-auto flex items-center gap-1.5 text-red hover:opacity-80"
                    >
                      <Trash2 size={15} /> Eliminar
                    </button>
                  </div>
                </div>
              );
            })}
          </div>

          {totalPages > 1 && (
            <Pager page={page} totalPages={totalPages} onChange={setPage} />
          )}
        </>
      )}
      {deletingOffer && (
        <ConfirmDialog
          title="Eliminar oferta"
          message="Quer mesmo eliminar esta oferta? Esta ação não pode ser desfeita."
          confirmLabel="Eliminar"
          loadingLabel="A eliminar…"
          danger
          loading={busyId === deletingOffer.id}
          onConfirm={confirmDelete}
          onCancel={() => setDeletingOffer(null)}
        />
      )}
    </div>
  );
}
