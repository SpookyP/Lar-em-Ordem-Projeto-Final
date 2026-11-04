import { useEffect, useMemo, useState } from "react";
import { Search } from "lucide-react";
import { getRecommendations } from "../../../services/api/offerService";
import { OfferRow } from "../../../components/ui/OfferRow";

export default function Offers() {
  const [offers, setOffers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [query, setQuery] = useState("");

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);

    getRecommendations()
      .then((data) => {
        if (active) setOffers(data);
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

  const filtered = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return offers;
    return offers.filter((offer) => {
      const haystack = [offer.title, offer.type, offer.partner?.companyName]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      return haystack.includes(term);
    });
  }, [offers, query]);

  return (
    <div className="space-y-16">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h1 className="font-display text-4xl text-teal">Ofertas</h1>
        <p className="font-display text-xl italic text-muted translate-y-6 sm:text-right">
          Vantagens exclusivas dos nossos parceiros para a tua casa.
        </p>
      </div>

      <div className="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-border bg-card">
        <div className="relative border-b border-border">
          <Search
            size={18}
            className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted"
          />
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Procurar por título, tipo ou empresa…"
            className="w-full bg-transparent py-3.5 pl-11 pr-4 text-text outline-none"
          />
        </div>

        {loading ? (
          <p className="p-4 text-muted">A carregar ofertas…</p>
        ) : error ? (
          <p className="p-4 text-red">{error}</p>
        ) : filtered.length === 0 ? (
          <p className="p-4 text-muted">
            {query
              ? "Nenhuma oferta corresponde à tua pesquisa."
              : "De momento não há ofertas disponíveis."}
          </p>
        ) : (
          <div className="divide-y divide-border">
            {filtered.map((offer, index) => (
              <OfferRow key={offer.id} offer={offer} index={index} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
