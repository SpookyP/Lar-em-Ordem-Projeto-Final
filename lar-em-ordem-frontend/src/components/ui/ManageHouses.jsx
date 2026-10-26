import { useState } from "react";
import { useHouses } from "../../hooks/useHouses";
import { useHousesPage } from "../../hooks/useHousesPage";
import {
  getProperty,
  deleteProperty,
} from "../../services/api/propertyService";
import { PropertyForm } from "./PropertyForm";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { Pager } from "./Pager";

export function ManageHouses() {
  const { refreshHouses } = useHouses();
  const {
    items: houses,
    page,
    totalPages,
    total,
    loading,
    error: listError,
    setPage,
    reload,
  } = useHousesPage({ perPage: 5 });

  const [editing, setEditing] = useState(null);
  const [loadingEdit, setLoadingEdit] = useState(false);
  const [error, setError] = useState("");
  const [confirmingId, setConfirmingId] = useState(null);
  const [deletingId, setDeletingId] = useState(null);

  const { activeHouse } = useAuth();
  const navigate = useNavigate();

  async function handleEdit(id) {
    setError("");
    setLoadingEdit(true);
    try {
      const property = await getProperty(id);
      setEditing(property);
    } catch {
      setError("Não foi possível carregar a habitação.");
    } finally {
      setLoadingEdit(false);
    }
  }

  async function handleDelete(id) {
    setError("");
    setDeletingId(id);
    try {
      await deleteProperty(id);
      await refreshHouses();

      if (activeHouse?.id === id) {
        navigate("/profiles");
        return;
      }

      setConfirmingId(null);

      if (houses.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        reload();
      }
    } catch {
      setError("Não foi possível eliminar a habitação.");
    } finally {
      setDeletingId(null);
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
          onUpdated={() => {
            setEditing(null);
            refreshHouses();
            reload();
          }}
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Gerir habitações</h2>
      {(error || listError) && (
        <p className="text-sm text-red-600">{error || listError}</p>
      )}
      {loading && <p className="text-sm text-muted">A carregar...</p>}
      {!loading && !listError && total === 0 && (
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

            {confirmingId === h.id ? (
              <div className="flex items-center gap-2">
                <span className="text-sm text-muted">Tens a certeza?</span>
                <button
                  onClick={() => handleDelete(h.id)}
                  disabled={deletingId === h.id}
                  className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 transition disabled:opacity-60"
                >
                  {deletingId === h.id ? "A eliminar..." : "Sim, eliminar"}
                </button>
                <button
                  onClick={() => setConfirmingId(null)}
                  className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-text hover:bg-surface transition"
                >
                  Cancelar
                </button>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleEdit(h.id)}
                  disabled={loadingEdit}
                  className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-navy hover:bg-surface transition disabled:opacity-60"
                >
                  Editar
                </button>
                <button
                  onClick={() => setConfirmingId(h.id)}
                  className="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 transition"
                >
                  Eliminar
                </button>
              </div>
            )}
          </li>
        ))}
      </ul>
      <Pager page={page} totalPages={totalPages} onChange={setPage} />
    </div>
  );
}
