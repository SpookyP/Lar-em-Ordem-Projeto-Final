import { useEffect, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { getProperties } from "../../services/api/propertyService";

export function HouseList({ onEnter }) {
  const [page, setPage] = useState(1);
  const [houses, setHouses] = useState([]);
  const [lastPage, setLastPage] = useState([1]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);
    getProperties(page).then((res) => {
      if (!active) return;
      setHouses(res.houses);
      setLastPage(res.lastPage);
      setLoading(false);
    });
    return () => {
      active = false;
    };
  }, [page]);

  return (
    <div className="border-t border-border p-4 flex flex-col gap-2">
      <p className="text-sm text-muted">Escolha a casa</p>

      {loading ? (
        <p className="py-4 text-center text-sm text-muted">A carregar...</p>
      ) : (
        houses.map((house) => (
          <button
            key={house.id}
            onClick={() => onEnter(house)}
            className="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-left transition hover:border-navy hover:bg-navy/5"
          >
            <span className="text-sm text-text">
              {house.label}
              <span className="block text-xs text-muted">{house.location}</span>
            </span>
            <span className="text-xs font-medium text-navy">
              {house.bondLabel}
            </span>
          </button>
        ))
      )}

      {lastPage > 1 && (
        <div className="flex items-center justify-center gap-4 pt-2">
          <button
            onClick={() => setPage((p) => p - 1)}
            disabled={page === 1 || loading}
            aria-label="Página anterior"
            className="flex h-8 w-8 items-center justify-center rounded-full border border-border text-navy transition hover:bg-navy/5 disabled:opacity-30 disabled:hover:bg-transparent"
          >
            <ChevronLeft size={16} />
          </button>
          <span className="text-xs text-muted">
            {page} / {lastPage}
          </span>
          <button
            onClick={() => setPage((p) => p + 1)}
            disabled={page === lastPage || loading}
            aria-label="Página seguinte"
            className="flex h-8 w-8 items-center justify-center rounded-full border border-border text-navy transition hover:bg-navy/5 disabled:opacity-30 disabled:hover:bg-transparent"
          >
            <ChevronRight size={16} />
          </button>
        </div>
      )}
    </div>
  );
}
