import { useNavigate } from "react-router-dom";
import { Compass } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";

export default function NotFound() {
  const navigate = useNavigate();
  const { isAuthenticated, activeProfile } = useAuth();

  function goHome() {
    if (!isAuthenticated) return navigate("/");
    if (!activeProfile) return navigate("/profiles");
    navigate(ROLE_INFO[activeProfile.name]?.home ?? "/profiles");
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-md rounded-2xl bg-card p-8 shadow-lg text-center">
        <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-navy">
          <Compass className="text-white" size={28} />
        </div>

        <p className="text-sm font-semibold text-muted">Erro 404</p>
        <h1 className="mt-1 font-display text-3xl text-navy">
          Página não encontrada
        </h1>
        <p className="mt-3 text-sm text-muted">
          A página que procura não existe.
        </p>

        <div className="mt-6 flex flex-col gap-3">
          <button
            onClick={goHome}
            className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Voltar ao início
          </button>

          <button
            onClick={() => navigate(-1)}
            className="text-sm text-muted transition hover:text-navy"
          >
            Voltar atrás
          </button>
        </div>
      </div>
    </div>
  );
}
