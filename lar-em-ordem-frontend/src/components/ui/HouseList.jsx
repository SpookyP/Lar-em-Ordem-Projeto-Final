import { useHouses } from "../../hooks/useHouses";

export function HouseList({ onEnter }) {
  const { houses, loading } = useHouses();

  return (
    <div className="border-t border-border p-4 flex flex-col gap-2">
      <p className="text-sm text-muted">Escolha a casa</p>

      {loading ? (
        <p className="py-4 text-center text-sm text-muted">A carregar...</p>
      ) : houses.length === 0 ? (
        <p className="py-4 text-center text-sm text-muted">
          Ainda não tem casas associadas.
        </p>
      ) : (
        <div className="flex flex-col gap-2 max-h-64 overflow-y-auto">
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
      )}
    </div>
  );
}
