import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { deleteAccount } from "../../services/api/accountService";

export function DeleteAccount() {
  const { logout } = useAuth();
  const navigate = useNavigate();
  const [confirming, setConfirming] = useState(false);
  const [status, setStatus] = useState(null);
  const [error, setError] = useState("");

  async function handleDelete() {
    setError("");
    setStatus("deleting");
    try {
      await deleteAccount();
      try {
        await logout();
      } catch {}
      navigate("/login");
    } catch (err) {
      setStatus(null);
      setError(
        err.response?.data?.message ?? "Não foi possível apagar a conta.",
      );
    }
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-red-600">Apagar conta</h2>
      <div className="flex flex-col gap-2 text-sm text-muted">
        <p>
          Esta ação remove a tua conta e termina a sessão. Não vais conseguir
          voltar a entrar e não é possível recuperar os dados. Vamos apagar:
        </p>
        <ul className="list-disc pl-5 flex flex-col gap-1">
          <li>Os teus dados pessoais (nome, email, NIF e contactos).</li>
          <li>
            As tuas casas, com as faturas, os consumos e os documentos do cofre,
            incluindo os ficheiros.
          </li>
          <li>
            Os teus pedidos de assistência e as respetivas fotos. Pedidos com
            uma proposta aceite ficam no histórico do prestador, mas sem o teu
            nome nem a tua descrição.
          </li>
          <li>
            Os teus perfis de prestador ou parceiro e os dados da empresa.
          </li>
        </ul>
        <p>
          Se administras condomínios, tens de os transferir antes de apagar a
          conta.
        </p>
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}

      {confirming ? (
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted">Tens mesmo a certeza?</span>
          <button
            onClick={handleDelete}
            disabled={status === "deleting"}
            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 transition"
          >
            {status === "deleting" ? "A apagar..." : "Sim, apagar conta"}
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
          className="self-start rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
        >
          Apagar conta
        </button>
      )}
    </div>
  );
}
