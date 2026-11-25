import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { deleteAccount } from "../../services/api/accountService";
import { getErrorMessage } from "../../utils/apiError";
import { accountTypes } from "../../data/registerFields";
import { ConfirmDialog } from "../ui/ConfirmDialog";

function describeAccount(roles) {
  if (roles.length === 1) {
    const label =
      accountTypes.find((t) => t.role === roles[0])?.label.toLowerCase() ??
      "perfil";
    return `a conta de ${label}`;
  }

  return "a conta e todos os perfis";
}

export function DeleteAccount() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [confirming, setConfirming] = useState(false);
  const [status, setStatus] = useState(null);
  const [error, setError] = useState("");
  const roles = user?.roles ?? [];

  async function handleDelete() {
    setError("");
    setStatus("deleting");
    try {
      await deleteAccount();
      try {
        await logout();
      } catch {
        // The account is already deleted: if the logout fails, ignore it and go to the login
      }
      navigate("/login");
    } catch (err) {
      setStatus(null);
      setConfirming(false);
      setError(getErrorMessage(err, "Não foi possível apagar a conta."));
    }
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-red-600">Apagar conta</h2>
      <div className="flex flex-col gap-2 text-sm text-muted">
        <p>
          Esta ação remove a sua conta e termina a sessão. Não vai conseguir
          voltar a entrar e não é possível recuperar os dados. Vamos apagar:
        </p>
        <ul className="list-disc pl-5 flex flex-col gap-1">
          <li>Os seus dados pessoais (nome, email, NIF e contactos).</li>
          <li>
            As suas casas, com as faturas, os consumos e os documentos do cofre,
            incluindo os ficheiros.
          </li>
          <li>
            Os seus pedidos de assistência e as respetivas fotografias. Pedidos
            com uma proposta aceite ficam no histórico do prestador, mas sem o
            seu nome e a sua descrição.
          </li>
          <li>
            Os seus perfis de prestador ou parceiro e os dados da empresa.
          </li>
        </ul>
        <p>
          Se administra condomínios, tem de os transferir antes de apagar a
          conta.
        </p>
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}

      <button
        onClick={() => setConfirming(true)}
        className="self-start rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
      >
        Apagar conta
      </button>

      {confirming && (
        <ConfirmDialog
          title="Apagar conta"
          message={`Tem mesmo a certeza que pretende eliminar ${describeAccount(roles)}? Esta ação não pode ser desfeita.`}
          confirmLabel="Apagar conta"
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
