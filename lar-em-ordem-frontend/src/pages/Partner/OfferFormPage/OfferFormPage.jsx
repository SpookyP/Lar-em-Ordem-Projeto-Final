import { useEffect, useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { ChevronLeft, Pencil } from "lucide-react";
import { OfferForm } from "../../../components/ui/OfferForm";
import { getOffer } from "../../../services/api/offerService";

const TITLES = {
  create: "Nova oferta",
  edit: "Editar oferta",
  view: "Detalhe da oferta",
};

export default function OfferFormPage({ mode }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const isView = mode === "view";
  const needsLoad = mode === "view" || mode === "edit";

  const [offer, setOffer] = useState(null);
  const [loading, setLoading] = useState(needsLoad);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!needsLoad) return;
    let active = true;
    getOffer(id)
      .then((data) => {
        if (active) setOffer(data);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar a oferta.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [id, needsLoad]);

  return (
    <div className="flex flex-col gap-6 max-w-2xl mx-auto">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <Link
            to="/partner/offers"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card shadow-sm text-muted transition hover:text-navy"
            aria-label="Voltar"
          >
            <ChevronLeft size={20} />
          </Link>
          <h1 className="font-display text-2xl text-navy">{TITLES[mode]}</h1>
        </div>

        {isView && offer && (
          <Link
            to={`/partner/offers/${id}/edit`}
            className="flex items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light"
          >
            <Pencil size={16} /> Editar
          </Link>
        )}
      </div>

      {error && <p className="text-sm text-red">{error}</p>}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        <div className="rounded-2xl bg-card p-6 shadow-sm">
          <OfferForm
            offer={offer}
            readOnly={isView}
            onSaved={() => navigate("/partner/offers")}
          />
        </div>
      )}
    </div>
  );
}
