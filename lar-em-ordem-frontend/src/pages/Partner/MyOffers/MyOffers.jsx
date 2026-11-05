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

const FILTERS = [
  { value: "", label: "Todas" },
  { value: "current", label: "Em vigor" },
  { value: "scheduled", label: "Agendadas" },
  { value: "expired", label: "Expiradas" },
  { value: "inactive", label: "Inativas" },
];

export default function MyOffers() {
  const [busyId, setBusyId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
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

  async function handleDelete(offer) {
    setBusyId(offer.id);
    setActionError("");
    try {
      await deleteOffer(offer.id);
      setConfirmingId(null);
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
        <p className="text-sm text-muted">A carregar...</p>
      ) : isEmptyAll ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <p className="text-sm text-muted">Ainda não tens ofertas.</p>
          <Link
            to="/partner/offers/new"
            className="mt-3 inline-block font-semibold text-navy hover:underline"
          >
            Criar a primeira oferta
          </Link>
        </div>
      ) : offers.length === 0 ? (
        <p className="text-sm text-muted">Nenhuma oferta neste estado.</p>
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
                    {confirmingId === offer.id ? (
                      <>
                        <span className="text-muted">
                          Eliminar esta oferta?
                        </span>
                        <button
                          onClick={() => handleDelete(offer)}
                          disabled={busyId === offer.id}
                          className="ml-auto rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
                        >
                          Sim, eliminar
                        </button>
                        <button
                          onClick={() => setConfirmingId(null)}
                          className="text-muted underline hover:text-navy"
                        >
                          Cancelar
                        </button>
                      </>
                    ) : (
                      <>
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
                          onClick={() => setConfirmingId(offer.id)}
                          className="ml-auto flex items-center gap-1.5 text-red hover:opacity-80"
                        >
                          <Trash2 size={15} /> Eliminar
                        </button>
                      </>
                    )}
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
    </div>
  );
}
