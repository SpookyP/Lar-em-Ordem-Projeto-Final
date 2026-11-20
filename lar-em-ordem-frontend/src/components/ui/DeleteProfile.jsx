import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { deleteProfile } from "../../services/api/accountService";
import { ConfirmDialog } from "./ConfirmDialog";
import { EmptyState } from "./EmptyState";

export function DeleteProfile() {
  const { activeProfile, refreshUser } = useAuth();
  const navigate = useNavigate();
  const [confirming, setConfirming] = useState(false);
  const [status, setStatus] = useState(null);
  const [error, setError] = useState("");

  const role = activeProfile?.name;
  const label = activeProfile?.label ?? role;

  async function handleDelete() {
    setError("");
    setStatus("deleting");
    try {
      await deleteProfile(role);
      await refreshUser();
      navigate("/profiles");
    } catch (err) {
      setStatus(null);
      setConfirming(false);
      setError(
        err.response?.data?.errors?.role?.[0] ??
          err.response?.data?.message ??
          "Não foi possível apagar o perfil.",
      );
    }
  }

  if (!role) return <EmptyState message="Nenhum perfil selecionado." />;

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Apagar perfil</h2>
      <p className="text-sm text-muted">
        Irá remover o perfil <span className="text-text">{label}</span> desta
        conta. Os outros perfis mantêm-se.
      </p>
      {role === "resident" && (
        <p className="text-sm text-muted">
          Ao apagar este perfil, são também apagadas as suas casas, faturas,
          consumos, documentos do cofre e pedidos de assistência, incluindo os
          ficheiros. Não é possível recuperar estes dados.
        </p>
      )}
      {error && <p className="text-sm text-red-600">{error}</p>}

      <button
        onClick={() => setConfirming(true)}
        className="self-start rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
      >
        Apagar perfil
      </button>

      {confirming && (
        <ConfirmDialog
          title="Apagar perfil"
          message={`Tem mesmo a certeza que pretende eliminar o perfil de ${label.toLowerCase()}? Esta ação não pode ser desfeita.`}
          confirmLabel="Apagar perfil"
          loadingLabel="A apagar…"
          danger
          loading={status === "deleting"}
          onConfirm={handleDelete}
          onCancel={() => setConfirming(false)}
        />
      )}
    </div>
  );
}
