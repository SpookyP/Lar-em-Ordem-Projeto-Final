import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { deleteProfile } from "../../services/api/accountService";

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
      navigate("/perfis");
    } catch (err) {
      setStatus(null);
      setError(
        err.response?.data?.errors?.role?.[0] ??
          err.response?.data?.message ??
          "Não foi possível apagar o perfil.",
      );
    }
  }

  if (!role)
    return <p className="text-sm text-muted">Nenhum perfil selecionado.</p>;

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Apagar perfil</h2>
      <p className="text-sm text-muted">
        Vais remover o perfil <span className="text-text">{label}</span> desta
        conta. Os outros perfis mantêm-se.
      </p>
      {error && <p className="text-sm text-red-600">{error}</p>}

      {confirming ? (
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted">Tens a certeza?</span>
          <button
            onClick={handleDelete}
            disabled={status === "deleting"}
            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 transition"
          >
            {status === "deleting" ? "A apagar..." : "Sim, apagar perfil"}
          </button>
          <button
            onClick={() => setConfirming(false)}
            className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text hover:bg-surface transition"
          >
            Cancelar
          </button>
        </div>
      ) : (
        <button
          onClick={() => setConfirming(true)}
          className="self-start rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition"
        >
          Apagar perfil
        </button>
      )}
    </div>
  );
}
