import { useNavigate } from "react-router-dom";
import { ShieldAlert } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";

export default function Unauthorized() {
  const navigate = useNavigate();
  const { user, activeProfile, logout } = useAuth();

  const roleCount = user?.roles?.length ?? 0;

  function goHome() {
    const home = ROLE_INFO[activeProfile?.name]?.home ?? "/profiles";
    navigate(home);
  }

  async function handleLogout() {
    await logout();
    navigate("/login");
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-md rounded-2xl bg-card p-8 shadow-lg text-center">
        <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-light">
          <ShieldAlert className="text-red" size={28} />
        </div>

        <p className="text-sm font-semibold text-muted">Erro 403</p>
        <h1 className="mt-1 font-display text-3xl text-navy">Acesso negado</h1>
        <p className="mt-3 text-sm text-muted">
          Não tens permissão para aceder a esta página
          {activeProfile ? ` com o perfil "${activeProfile.label}"` : ""}.
        </p>

        <div className="mt-6 flex flex-col gap-3">
          <button
            onClick={goHome}
            className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Voltar ao início
          </button>

          {roleCount > 1 && (
            <button
              onClick={() => navigate("/profiles")}
              className="rounded-xl border border-border px-5 py-3 font-medium text-text transition hover:bg-surface"
            >
              Trocar de perfil
            </button>
          )}

          <button
            onClick={handleLogout}
            className="text-sm text-muted transition hover:text-navy"
          >
            Terminar sessão
          </button>
        </div>
      </div>
    </div>
  );
}
