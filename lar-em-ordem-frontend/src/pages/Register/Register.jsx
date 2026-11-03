import { useState, useEffect } from "react";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import {
  getSocialPending,
  resendRegistration,
} from "../../services/api/authService";
import { SocialLoginButtons } from "../../components/ui/SocialLoginButtons";
import { ProfileWizard } from "../../components/profile/ProfileWizard";

const PROVIDER_NAMES = { google: "Google", microsoft: "Microsoft" };

function Card({ children, className = "" }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-surface p-6">
      <div
        className={`w-full max-w-lg rounded-2xl border border-border bg-card p-8 ${className}`}
      >
        {children}
      </div>
    </div>
  );
}

export default function Register() {
  const { register, registerSocial } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const wantsSocial = searchParams.get("social") === "1";

  const [social, setSocial] = useState(null);
  const [socialStatus, setSocialStatus] = useState(
    wantsSocial ? "checking" : "idle",
  );
  const [sentTo, setSentTo] = useState("");
  const [resend, setResend] = useState("idle"); // idle | sending | sent | failed

  useEffect(() => {
    if (!wantsSocial) return;

    // StrictMode runs this twice in development; ignore the first answer
    let cancelled = false;

    getSocialPending()
      .then((data) => {
        if (cancelled) return;
        setSocial(data);
        setSocialStatus("ok");
      })
      .catch(() => {
        if (!cancelled) setSocialStatus("expired");
      });

    return () => {
      cancelled = true;
    };
  }, [wantsSocial]);

  const socialMode = wantsSocial && socialStatus === "ok" && social !== null;
  const providerName = Object.hasOwn(PROVIDER_NAMES, social?.provider ?? "")
    ? PROVIDER_NAMES[social.provider]
    : "o seu fornecedor";

  async function handleSubmit(payload) {
    const result = socialMode
      ? await registerSocial(payload)
      : await register(payload);

    if (result.pending) {
      setSentTo(socialMode ? social.email : payload.email);
      return;
    }

    navigate("/profiles");
  }

  async function handleResend() {
    setResend("sending");
    try {
      await resendRegistration(sentTo);
      setResend("sent");
    } catch {
      setResend("failed");
    }
  }

  if (sentTo) {
    return (
      <Card className="text-center">
        <h1 className="font-display text-3xl text-navy">
          Confirme o seu email
        </h1>
        <p className="mt-3 text-sm text-muted">
          Enviámos um link para{" "}
          <span className="font-medium text-text">{sentTo}</span>. A conta só é
          criada depois de o abrir, nos próximos 10 minutos (pode ser noutro
          dispositivo).
        </p>
        <p className="mt-2 text-sm text-muted">
          {socialMode
            ? `Depois, volte aqui e entre com ${providerName}.`
            : "Depois, volte aqui e inicie sessão."}
        </p>

        {resend === "failed" && (
          <p className="mt-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            Não foi possível enviar o email. Tente novamente.
          </p>
        )}

        <div className="mt-6 flex flex-col gap-3">
          <button
            type="button"
            onClick={handleResend}
            disabled={resend === "sending" || resend === "sent"}
            className="rounded-xl border border-border px-5 py-3 font-medium text-text transition hover:bg-surface disabled:opacity-50"
          >
            {resend === "sent"
              ? "Email enviado"
              : resend === "sending"
                ? "A enviar..."
                : "Reenviar email"}
          </button>
          <Link
            to="/login"
            className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Ir para o login
          </Link>
        </div>
      </Card>
    );
  }

  if (wantsSocial && socialStatus === "checking") {
    return (
      <div className="flex min-h-screen items-center justify-center bg-surface p-6">
        <p className="text-muted">A verificar o registo...</p>
      </div>
    );
  }

  if (wantsSocial && socialStatus === "expired") {
    return (
      <Card>
        <h1 className="font-display text-3xl text-navy">O registo expirou</h1>
        <p className="mb-6 mt-2 text-sm text-muted">
          Volte a entrar com Google ou Microsoft para continuar.
        </p>
        <SocialLoginButtons />
        <Link
          to="/register"
          className="block text-center text-sm text-muted hover:text-navy"
        >
          Criar conta com email
        </Link>
      </Card>
    );
  }

  return (
    <Card>
      <h1 className="font-display text-3xl text-navy">Criar conta</h1>
      {socialMode && (
        <p className="mt-2 text-sm text-muted">
          A continuar com {providerName}:{" "}
          <span className="font-medium text-text">{social.name}</span> (
          {social.email}).
        </p>
      )}

      <div className="mt-6">
        <ProfileWizard
          key={socialMode ? "social" : "email"}
          withAccount={!socialMode}
          accountIntro={<SocialLoginButtons />}
          onSubmit={handleSubmit}
          submitLabel="Criar conta"
          submittingLabel="A criar conta..."
          footer={
            socialMode ? (
              <Link
                to="/register"
                className="text-center text-sm text-muted hover:text-navy"
              >
                Cancelar e usar outro método
              </Link>
            ) : (
              <p className="text-center text-sm text-muted">
                Já tem conta?{" "}
                <Link to="/login" className="font-medium text-navy">
                  Entrar
                </Link>
              </p>
            )
          }
        />
      </div>
    </Card>
  );
}
