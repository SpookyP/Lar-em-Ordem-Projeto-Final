import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { getRecommendations } from "../../services/api/offerService";
import { PartnerOfferCard } from "./PartnerOfferCard";
import { ArrowRight } from "lucide-react";

export function OfferRecommendations() {
  const [offers, setOffers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getRecommendations({ perPage: 4 })
      .then((data) => {
        if (active) setOffers(data.items);
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

  if (loading)
    return <p className="text-sm text-muted">A carregar ofertas...</p>;
  if (error) return <p className="text-sm text-red">{error}</p>;
  if (offers.length === 0) return null;

  const preview = offers.slice(0, 4);

  return (
    <section className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-xl text-navy">
          Poupe já na próxima fatura
        </h2>
        <Link
          to="/offers"
          className="flex items-center gap-1 text-sm font-medium text-navy hover:underline"
        >
          Ver todas <ArrowRight size={15} />
        </Link>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {preview.map((offer, i) => (
          <PartnerOfferCard key={offer.id} offer={offer} index={i} />
        ))}
      </div>

      <p className="text-xs text-muted">
        Conteúdo comercial de parceiros certificados Lar em Ordem.
      </p>
    </section>
  );
}
