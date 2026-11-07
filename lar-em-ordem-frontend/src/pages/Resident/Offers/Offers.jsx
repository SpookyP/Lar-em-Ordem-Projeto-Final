import { useEffect, useState } from "react";
import { Search } from "lucide-react";
import { OfferRow } from "../../../components/ui/OfferRow";
import { getOfferCategories } from "../../../services/api/offerService";
import { useOffersPage } from "../../../hooks/useOffersPage";
import { useDebouncedValue } from "../../../hooks/useDebouncedValue";
import { Pager } from "../../../components/ui/Pager";

export default function Offers() {
  const [searchInput, setSearchInput] = useState("");
  const search = useDebouncedValue(searchInput.trim());
  const [categoryId, setCategoryId] = useState("");
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    let active = true;
    getOfferCategories()
      .then((data) => {
        if (active) setCategories(data);
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, []);

  const {
    items: offers,
    page,
    totalPages,
    loading,
    error,
    setPage,
  } = useOffersPage({ perPage: 12, search, categoryId });

  const hasFilters = search !== "" || categoryId !== "";

  return (
    <div className="space-y-16">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h1 className="font-display text-4xl text-teal">Ofertas</h1>
        <p className="font-display text-xl italic text-muted translate-y-6 sm:text-right">
          Vantagens exclusivas dos nossos parceiros para a tua casa.
        </p>
      </div>

      <div className="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-border bg-card">
        <div className="flex flex-col gap-3 border-b border-border p-3 sm:flex-row sm:items-center">
          <div className="relative flex-1">
            <Search
              size={18}
              className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"
            />
            <input
              type="search"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Procurar por título, descrição ou empresa…"
              className="w-full rounded-lg border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text outline-none focus:border-navy"
            />
          </div>

          <select
            value={categoryId}
            onChange={(e) => setCategoryId(e.target.value)}
            className="rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none focus:border-navy sm:w-56"
          >
            <option value="">Todas as categorias</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </div>

        {loading && offers.length === 0 ? (
          <p className="p-4 text-muted">A carregar ofertas…</p>
        ) : error ? (
          <p className="p-4 text-red">{error}</p>
        ) : offers.length === 0 ? (
          <p className="p-4 text-muted">
            {hasFilters
              ? "Nenhuma oferta corresponde aos filtros."
              : "De momento não há ofertas disponíveis."}
          </p>
        ) : (
          <div className="divide-y divide-border">
            {offers.map((offer, index) => (
              <OfferRow key={offer.id} offer={offer} index={index} />
            ))}
          </div>
        )}

        {totalPages > 1 && (
          <div className="border-t border-border p-3">
            <Pager
              page={page}
              totalPages={totalPages}
              onChange={setPage}
              bordered={false}
            />
          </div>
        )}
      </div>
    </div>
  );
}
