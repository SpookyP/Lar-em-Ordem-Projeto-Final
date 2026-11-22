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
import { Pager } from "../ui/Pager";
import { ConfirmDialog } from "../ui/ConfirmDialog";
import { LoadingState } from "../ui/LoadingState";
import { EmptyState } from "../ui/EmptyState";

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
  const [deletingHouse, setDeletingHouse] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const { activeHouse, clearActiveHouse } = useAuth();
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

  async function confirmDelete() {
    if (!deletingHouse) return;
    const { id } = deletingHouse;
    setError("");
    setDeleting(true);
    try {
      await deleteProperty(id);
      await refreshHouses();

      if (activeHouse?.id === id) {
        clearActiveHouse();
        navigate("/profiles");
        return;
      }

      setDeletingHouse(null);

      if (houses.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        reload();
      }
    } catch {
      setError("Não foi possível eliminar a habitação.");
    } finally {
      setDeleting(false);
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
      {loading && <LoadingState />}
      {!loading && !listError && total === 0 && (
        <EmptyState message="Ainda não tens habitações." />
      )}

      <ul className="flex flex-col gap-2">
        {houses.map((h) => (
          <li
            key={h.id}
            className="flex flex-col gap-3 rounded-lg border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
          >
            <div className="min-w-0 text-sm">
              <p className="text-text">{h.label}</p>
              <p className="text-muted">
                {h.location} · {h.bondLabel}
              </p>
            </div>

            <div className="flex shrink-0 items-center gap-2">
              <button
                onClick={() => handleEdit(h.id)}
                disabled={loadingEdit}
                className="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-navy hover:bg-surface transition disabled:opacity-60"
              >
                Editar
              </button>
              <button
                onClick={() => setDeletingHouse(h)}
                className="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 transition"
              >
                Eliminar
              </button>
            </div>
          </li>
        ))}
      </ul>
      <Pager page={page} totalPages={totalPages} onChange={setPage} />
      {deletingHouse && (
        <ConfirmDialog
          title="Eliminar habitação"
          message={`Quer mesmo eliminar esta habitação? Esta ação não pode ser desfeita. Irá perder os dados da habitação.`}
          confirmLabel="Eliminar"
          loadingLabel="A eliminar…"
          danger
          loading={deleting}
          onConfirm={confirmDelete}
          onCancel={() => setDeletingHouse(null)}
        />
      )}
    </div>
  );
}
