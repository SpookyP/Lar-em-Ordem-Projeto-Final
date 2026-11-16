import { useState } from "react";
import { useAuth } from "../../hooks/useAuth";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import { SocialLoginButtons } from "../../components/ui/SocialLoginButtons";
import { socialErrorMessage } from "../../utils/socialErrors";
import { TextInput, PasswordField } from "../../components/form";

export default function Login() {
  const { login, refreshUser } = useAuth();
  const navigate = useNavigate();

  const [searchParams] = useSearchParams();
  const errorSocial = socialErrorMessage(searchParams.get("erro"));

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
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
      } else if (status === 409) {
        const current = await refreshUser().catch(() => null);
        if (current) return;
      }
      setError(message);
    } finally {
      setSubmitting(false);
    }
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
          <TextInput
            label="Email"
            type="email"
            required
            value={email}
            onChange={setEmail}
            autoComplete="email"
          />
          <PasswordField
            label="Password"
            required
            value={password}
            onChange={setPassword}
            autoComplete="current-password"
          />
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