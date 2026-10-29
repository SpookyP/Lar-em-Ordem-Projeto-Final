import { useState } from "react";
import { useAuth } from "../../hooks/useAuth";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import { PasswordInput } from "../../components/ui/PasswordInput";
import { SocialLoginButtons } from "../../components/ui/SocialLoginButtons";
import { socialErrorMessage } from "../../utils/socialErrors";

export default function Login() {
  const { login, isAuthenticated, logout } = useAuth();
  const navigate = useNavigate();
  
  const [searchParams] = useSearchParams();
  const errorSocial = socialErrorMessage(searchParams.get("erro"));

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setErro("");
    setSubmitting(true);
    try {
      await login(email, password);
      navigate("/profiles");
    } catch (requestError) {
      const status = requestError.response?.status;
      let message = "Não foi possível iniciar sessão. Tente novamente.";

      if (status === 422 || status === 401) {
        message = "Email ou password incorretos.";
      } else if (status === 429) {
        message = "Demasiadas tentativas. Aguarde um pouco e tente novamente.";
      }
      setErro(message);
    } finally {
      setSubmitting(false);
    }
  }

  async function handleLogout() {
    await logout();
  }

  if (isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-surface px-4">
        <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg text-center">
          <h1 className="font-display text-2xl text-navy">
            Já existe uma sessão iniciada
          </h1>
          <p className="mt-2 text-sm text-muted">
            Clique em continuar para aceder à aplicação ou termine sessão.
          </p>
          <div className="mt-6 flex flex-col gap-3">
            <button
              onClick={() => navigate("/profiles")}
              className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
            >
              Continuar
            </button>
            <button
              onClick={handleLogout}
              className="rounded-xl border border-border px-5 py-3 font-medium text-text transition hover:bg-surface"
            >
              Terminar sessão
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg">
        <h1 className="font-display text-3xl text-center text-navy">Login</h1>
        <p className="mt-1 mb-6 text-sm text-center text-muted">
          Inicie sessão para continuar
        </p>

        {(error || errorSocial) && (
          <div className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {error || errorSocial}
          </div>
        )}

        <SocialLoginButtons />

        <form className="flex flex-col gap-6" onSubmit={handleSubmit}>
          <label className=" flex flex-col gap-4">
            <span className="text-sm font-medium text-text mr-4">Email</span>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>
          <label className=" flex flex-col gap-4">
            <span className="text-sm font-medium text-text mr-2">Password</span>
            <PasswordInput
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>
          <button
            type="submit"
            disabled={submitting}
            className="mt-2 rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            {submitting ? "A entrar..." : "Entrar"}
          </button>
          <div className="mt-3 text-center">
            <Link
              to="/forgot-password"
              className="text-sm font-medium text-navy hover:underline"
            >
              Esqueceu-se da password?
            </Link>
          </div>
          <p className="-mt-3 text-center text-sm text-muted">
            Não tem conta?{" "}
            <Link to="/register" className="font-medium text-navy">
              Criar conta
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
