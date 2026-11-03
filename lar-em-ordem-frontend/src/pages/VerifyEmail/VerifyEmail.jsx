import { useEffect, useState } from "react";
import { useSearchParams, useNavigate, Link } from "react-router-dom";
import { MailCheck, MailWarning } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import {
  verifyEmail,
  resendVerification,
  confirmRegistration,
} from "../../services/api/authService";
import { getErrorMessage } from "../../utils/apiError";

export default function VerifyEmail() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const {
    isAuthenticated,
    loading: authLoading,
    refreshUser,
    logout,
  } = useAuth();

  const token = searchParams.get("token");

  const [status, setStatus] = useState("verifying");
  const [error, setError] = useState("");
  const [resending, setResending] = useState(false);
  const [resent, setResent] = useState(false);

  useEffect(() => {
    if (authLoading) return;

    const id = searchParams.get("id");
    const hash = searchParams.get("hash");
    const expires = searchParams.get("expires");
    const signature = searchParams.get("signature");

    if (!token && (!id || !hash || !expires || !signature)) {
      setStatus("error");
      setError("Link inválido.");
      return;
    }

    let cancelled = false;

    const request = token
      ? confirmRegistration(token).then(() => refreshUser().catch(() => {}))
      : verifyEmail(id, hash, { expires, signature }).then(async () => {
          if (isAuthenticated) await refreshUser().catch(() => {});
        });

    request.then(() => {
        if (!cancelled) setStatus("success");
      })
      .catch((requestError) => {
        if (cancelled) return;
        setStatus("error");

        setError(
          requestError.response?.data?.errors?.token?.[0] ??
            requestError.response?.data?.message ??
            getErrorMessage(
              requestError,
              "Não foi possível confirmar o email.",
            ),
        );
      });

    return () => {
      cancelled = true;
    };
  }, [searchParams.toString(), authLoading]);

  async function handleResend() {
    setResending(true);
    try {
      await resendVerification();
      setResent(true);
    } catch {
      setError("Não foi possível enviar o email. Tente novamente.");
    } finally {
      setResending(false);
    }
  }

  async function handleLogout() {
    await logout();
    navigate("/login");
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-md rounded-2xl bg-card p-8 shadow-lg text-center">
        {status === "verifying" && (
          <>
            <p className="text-sm font-semibold text-muted">A confirmar</p>
            <h1 className="mt-1 font-display text-3xl text-navy">
              Um momento...
            </h1>
            <p className="mt-3 text-sm text-muted">
              Estamos a confirmar o seu email.
            </p>
          </>
        )}

        {status === "success" && (
          <>
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-light">
              <MailCheck className="text-green" size={28} />
            </div>
            <h1 className="font-display text-3xl text-navy">
              {token ? "Conta criada" : "Email confirmado"}
            </h1>
            <p className="mt-3 text-sm text-muted">
              {!token
                ? "O seu novo email está confirmado."
                : isAuthenticated
                  ? "O seu email foi confirmado e a conta está pronta a usar."
                  : "O seu email foi confirmado. Inicie sessão para continuar."}
            </p>
            <button
              onClick={() => navigate(isAuthenticated ? "/profiles" : "/login")}
              className="mt-6 w-full rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
            >
              {isAuthenticated ? "Continuar" : "Iniciar sessão"}
            </button>
          </>
        )}

        {status === "error" && (
          <>
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-light">
              <MailWarning className="text-red" size={28} />
            </div>
            <h1 className="font-display text-3xl text-navy">
              Não foi possível confirmar
            </h1>
            <p className="mt-3 text-sm text-muted">{error}</p>

            {token ? (
              <div className="mt-6 flex flex-col gap-3">
                <Link
                  to="/register"
                  className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
                >
                  Voltar ao registo
                </Link>
                <Link
                  to="/login"
                  className="text-sm text-muted transition hover:text-navy"
                >
                  Ir para o login
                </Link>
              </div>
            ) : isAuthenticated ? (
              <div className="mt-6 flex flex-col gap-3">
                <button
                  onClick={handleResend}
                  disabled={resending || resent}
                  className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light disabled:opacity-50"
                >
                  {resent
                    ? "Email enviado"
                    : resending
                      ? "A enviar..."
                      : "Enviar novo email"}
                </button>
                <button
                  onClick={handleLogout}
                  className="text-sm text-muted transition hover:text-navy"
                >
                  Terminar sessão
                </button>
              </div>
            ) : (
              <Link
                to="/login"
                className="mt-6 inline-block rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
              >
                Ir para o login
              </Link>
            )}
          </>
        )}
      </div>
    </div>
  );
}
