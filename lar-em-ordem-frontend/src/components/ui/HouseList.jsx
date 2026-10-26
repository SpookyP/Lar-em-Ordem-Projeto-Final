import { useHousesPage } from "../../hooks/useHousesPage";
import { useNavigate } from "react-router-dom";
import { Pager } from "./Pager";

export function HouseList({ onEnter }) {
  const {
    items: houses,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useHousesPage({ perPage: 5 });
  const navigate = useNavigate();

  return (
    <div className="border-t border-border p-4 flex flex-col gap-2">
      <p className="text-sm text-muted">Escolha a casa</p>

      {loading ? (
        <p className="py-4 text-center text-sm text-muted">A carregar...</p>
      ) : error ? (
        <p className="py-4 text-center text-sm text-red-600">{error}</p>
      ) : total === 0 ? (
        <div className="flex flex-col items-center gap-3 py-4 text-center">
          <p className="text-center text-sm text-muted">
            Ainda não tem casas associadas.
          </p>
          <button
            onClick={() => navigate("/welcome")}
            className="rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-light"
          >
            Criar a minha primeira habitação
          </button>
        </div>
      ) : (
        <>
          <div className="flex flex-col gap-2">
            {houses.map((house) => (
              <button
                key={house.id}
                onClick={() => onEnter(house)}
                className="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-left transition hover:border-navy hover:bg-navy/5"
              >
                <span className="text-sm text-text">
                  {house.label}
                  <span className="block text-xs text-muted">
                    {house.location}
                  </span>
                </span>
                <span className="text-xs font-medium text-navy">
                  {house.bondLabel}
                </span>
              </button>
            ))}
          </div>
          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}
    </div>
  );
}
