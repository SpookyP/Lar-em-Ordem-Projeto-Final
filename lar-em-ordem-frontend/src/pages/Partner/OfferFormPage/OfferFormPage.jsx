import { useEffect, useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { ChevronLeft } from "lucide-react";
import { OfferForm } from "../../../components/ui/OfferForm";
import { getOffer } from "../../../services/api/offerService";

export default function OfferFormPage() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();

  const [offer, setOffer] = useState(null);
  const [loading, setLoading] = useState(isEdit);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!isEdit) return;
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
  }, [id, isEdit]);

  return (
    <div className="flex flex-col gap-6 max-w-2xl mx-auto">
      <div className="flex items-center gap-3">
        <Link
          to="/partner/offers"
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card shadow-sm text-muted transition hover:text-navy"
          aria-label="Voltar"
        >
          <ChevronLeft size={20} />
        </Link>
        <h1 className="font-display text-2xl text-navy">
          {isEdit ? "Editar oferta" : "Nova oferta"}
        </h1>
      </div>

      {error && <p className="text-sm text-red">{error}</p>}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        <div className="rounded-2xl bg-card p-6 shadow-sm">
          <OfferForm
            offer={offer}
            onSaved={() => navigate("/partner/offers")}
          />
        </div>
      )}
    </div>
  );
}
