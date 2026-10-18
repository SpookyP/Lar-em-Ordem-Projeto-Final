import { useNavigate } from "react-router-dom";
import { PropertyForm } from "../../components/ui/PropertyForm";
import { useAuth } from "../../hooks/useAuth";

export default function Onboarding() {
  const navigate = useNavigate();
  const { logout } = useAuth();

  async function handleLogout() {
    await logout();
    navigate("/login");
  }

  return (
    <div className="min-h-screen bg-surface flex items-center justify-center p-6">
      <div className="w-full max-w-2xl rounded-3xl bg-card border border-border overflow-hidden">
        <div className="max-h-[85vh] overflow-y-auto p-8">
          <h1 className="font-display text-3xl text-navy">Bem-vindo(a)!</h1>
          <p className="text-muted mt-1 mb-6">
            Para começar, insira a sua primeira habitação.
          </p>

          <PropertyForm
            onCreated={() => navigate("/perfis")}
            secondaryAction={
              <button
                type="button"
                onClick={handleLogout}
                className="self-start rounded-lg border border-border px-4 py-2 text-sm font-medium text-navy hover:bg-surface transition"
              >
                Terminar sessão
              </button>
            }
          />
        </div>
      </div>
    </div>
  );
}
