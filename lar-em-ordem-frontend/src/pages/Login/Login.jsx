import { useState } from "react";
import { ArrowRight } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import { SocialLoginButtons } from "../../components/ui/SocialLoginButtons";
import { socialErrorMessage } from "../../utils/socialErrors";
import { TextInput, PasswordField } from "../../components/form";
import { AuthSplit } from "../../components/layout/AuthSplit";
import { ServiceStatus } from "../../components/ui/ServiceStatus";
import { InstallButton } from "../../components/ui/InstallButton";

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
    <AuthSplit>
      <div className="w-full">
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal">
          Bem-vindo de volta
        </p>
        <h1 className="mt-2 font-display text-4xl text-navy">Iniciar sessão</h1>
        <p className="mt-2 mb-5 text-sm text-muted">
          Aceda à sua casa, aos seus documentos e aos seus pedidos.
        </p>

        {(error || errorSocial) && (
          <div role="alert" className="mb-5 rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red">
            {error || errorSocial}
          </div>
        )}

        <SocialLoginButtons />

        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <TextInput
            label="Email"
            type="email"
            required
            value={email}
            onChange={setEmail}
            autoComplete="email"
            placeholder="nome@exemplo.pt"
          />
          <PasswordField
            label="Password"
            required
            value={password}
            onChange={setPassword}
            autoComplete="current-password"
          />
          <Link
            to="/forgot-password"
            className="-mt-1 self-end text-sm font-medium text-teal hover:underline"
          >
            Esqueceu-se da password?
          </Link>
          <button
            type="submit"
            disabled={submitting}
            className="flex items-center justify-center gap-2 rounded-xl bg-navy px-5 py-3 font-semibold text-white shadow-lg shadow-navy/20 transition hover:bg-navy-light disabled:opacity-60"
          >
            {submitting ? "A entrar..." : "Entrar"}
            {!submitting && <ArrowRight size={18} aria-hidden="true" />}
          </button>
          <p className="text-center text-sm text-muted">
            Ainda não tem conta?{" "}
            <Link to="/register" className="font-semibold text-navy hover:underline">
              Criar conta
            </Link>
          </p>
        </form>

        <div className="mt-6 flex flex-col gap-4 border-t border-border pt-5">
          <InstallButton />
          <ServiceStatus />
        </div>
      </div>
    </AuthSplit>
  );
}