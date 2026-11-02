import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { Plus, Pencil, Trash2, Eye, EyeOff } from "lucide-react";
import {
  getMyOffers,
  deleteOffer,
  setOfferActive,
} from "../../../services/api/offerService";
import { formatDate } from "../../../utils/date";
import { getOfferStatus } from "../../../utils/offerStatus";

const FILTERS = [
  { match: "all", label: "Todas" },
  { match: "Em vigor", label: "Em vigor" },
  { match: "Agendada", label: "Agendadas" },
  { match: "Expirada", label: "Expiradas" },
  { match: "Inativa", label: "Inativas" },
];

export default function MyOffers() {
  const [offers, setOffers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [busyId, setBusyId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [filter, setFilter] = useState("all");

  useEffect(() => {
    let active = true;
    setLoading(true);
    getMyOffers()
      .then((data) => {
        if (active) {
          setOffers(data);
          setError("");
        }
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar as ofertas.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  const counts = useMemo(() => {
    const c = { all: offers.length };
    offers.forEach((o) => {
      const { label } = getOfferStatus(o);
      c[label] = (c[label] ?? 0) + 1;
    });
    return c;
  }, [offers]);

  const visible = offers.filter(
    (o) => filter === "all" || getOfferStatus(o).label === filter,
  );

  async function handleToggle(offer) {
    setBusyId(offer.id);
    try {
      const updated = await setOfferActive(offer.id, !offer.active);
      setOffers((list) => list.map((o) => (o.id === offer.id ? updated : o)));
    } catch {
      setError("Não foi possível atualizar a oferta.");
    } finally {
      setBusyId(null);
    }
  }

  async function handleDelete(offer) {
    setBusyId(offer.id);
    try {
      await deleteOffer(offer.id);
      setOffers((list) => list.filter((o) => o.id !== offer.id));
      setConfirmingId(null);
    } catch {
      setError("Não foi possível eliminar a oferta.");
    } finally {
      setBusyId(null);
    }
  }

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

      {offers.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {FILTERS.map((f) => (
            <button
              key={f.match}
              onClick={() => setFilter(f.match)}
              className={`rounded-full px-3 py-1.5 text-sm font-medium transition ${
                filter === f.match
                  ? "bg-navy text-white"
                  : "bg-card text-muted hover:text-navy"
              }`}
            >
              {f.label}{" "}
              <span className="opacity-70">({counts[f.match] ?? 0})</span>
            </button>
          ))}
        </div>
      )}

      {error && <p className="text-sm text-red">{error}</p>}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : offers.length === 0 ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <p className="text-sm text-muted">Ainda não tens ofertas.</p>
          <Link
            to="/partner/offers/new"
            className="mt-3 inline-block font-semibold text-navy hover:underline"
          >
            Criar a primeira oferta
          </Link>
        </div>
      ) : visible.length === 0 ? (
        <p className="text-sm text-muted">Nenhuma oferta neste estado.</p>
      ) : (
        <div className="grid gap-4 lg:grid-cols-2">
          {visible.map((offer) => {
            const status = getOfferStatus(offer);
            return (
              <div
                key={offer.id}
                className="rounded-2xl bg-card p-5 shadow-sm flex flex-col gap-3"
              >
                <div className="flex items-center gap-2">
                  <h2 className="font-display text-lg text-navy">
                    {offer.title}
                  </h2>
                  <span
                    className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${status.className}`}
                  >
                    {status.label}
                  </span>
                </div>

                <p className="text-sm text-muted">{offer.type}</p>
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
                      <span className="text-muted">Eliminar esta oferta?</span>
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
                        to={`/partner/offers/${offer.id}`}
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
                            <EyeOff size={15} /> Inativar
                          </>
                        ) : (
                          <>
                            <Eye size={15} /> Ativar
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
      )}
    </div>
  );
}
