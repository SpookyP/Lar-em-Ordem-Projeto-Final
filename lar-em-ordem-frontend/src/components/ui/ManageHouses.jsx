import { useState } from "react";
import { useHouses } from "../../hooks/useHouses";
import { getProperty } from "../../services/api/propertyService";
import { PropertyForm } from "./PropertyForm";

export function ManageHouses() {
  const { houses, loading } = useHouses();
  const [editing, setEditing] = useState(null);
  const [loadingEdit, setLoadingEdit] = useState(false);
  const [error, setError] = useState("");

  async function handleEdit(id) {
    setError("");
    setLoadingEdit(true);
    try {
      const property = await getProperty(id); // detalhes completos (com ids)
      setEditing(property);
    } catch {
      setError("Não foi possível carregar a habitação.");
    } finally {
      setLoadingEdit(false);
    }
  }

  if (editing) {
    return (
      <div className="flex flex-col gap-4">
        <div className="flex items-center justify-between">
          <h2 className="font-medium text-text">Editar habitação</h2>
          <button
            onClick={() => setEditing(null)}
            className="text-sm text-navy hover:underline"
          >
            ← Voltar à lista
          </button>
        </div>
        <PropertyForm
          key={editing.id}
          property={editing}
          onUpdated={() => setEditing(null)}
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Gerir habitações</h2>
      {error && <p className="text-sm text-red-600">{error}</p>}
      {loading && <p className="text-sm text-muted">A carregar...</p>}
      {!loading && houses.length === 0 && (
        <p className="text-sm text-muted">Ainda não tens habitações.</p>
      )}

      <ul className="flex flex-col gap-2">
        {houses.map((h) => (
          <li
            key={h.id}
            className="flex items-center justify-between rounded-lg border border-border p-3"
          >
            <div className="text-sm">
              <p className="text-text">{h.label}</p>
              <p className="text-muted">
                {h.location} · {h.bondLabel}
              </p>
            </div>
            <button
              onClick={() => handleEdit(h.id)}
              disabled={loadingEdit}
              className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-navy hover:bg-surface transition disabled:opacity-60"
            >
              Editar
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
