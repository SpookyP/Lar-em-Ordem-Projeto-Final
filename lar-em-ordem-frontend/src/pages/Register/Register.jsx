import { useState, useEffect } from "react";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import { accountTypes, roleFields } from "../../data/registerFields";
import { useAuth } from "../../hooks/useAuth";
import { Field } from "../../components/ui/Field";
import { isPasswordValid } from "../../utils/passwordValidation";
import { PasswordRequirements } from "../../components/ui/PasswordRequirements";
import { SocialLoginButtons } from "../../components/ui/SocialLoginButtons";
import {
  getSocialPending,
  resendRegistration,
} from "../../services/api/authService";
import { getErrorMessage } from "../../utils/apiError";

const INITIAL_FORM = {
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  nif: "",
  company_name: "",
  phone: "",
  provider_email: "",
  website: "",
  description: "",
};

const PROVIDER_NAMES = { google: "Google", microsoft: "Microsoft" };

export default function Register() {
  const [form, setForm] = useState(INITIAL_FORM);
  const [error, setError] = useState("");
  const [step, setStep] = useState(1);
  const [role, setRole] = useState("");
  const [loading, setLoading] = useState(false);
  const [sentTo, setSentTo] = useState("");
  const [resending, setResending] = useState(false);
  const [resent, setResent] = useState(false);

  const { register, registerSocial } = useAuth();

  const navigate = useNavigate();

  const [searchParams] = useSearchParams();
  const wantsSocial = searchParams.get("social") === "1";
  const [social, setSocial] = useState(null);
  const [socialStatus, setSocialStatus] = useState(
    wantsSocial ? "checking" : "idle",
  );

  useEffect(() => {
    if (!wantsSocial) return;

    //em desenvolvimento (StrictMode) este efeito corre duas vezes: ignora a resposta da primeira
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

  const checkingSocial = wantsSocial && socialStatus === "checking";
  const socialExpired = wantsSocial && socialStatus === "expired";
  const socialMode = wantsSocial && socialStatus === "ok" && social !== null;

  //no registo social nao ha passo 1: nome e email vieram do fornecedor e nao ha password
  const currentStep = socialMode ? 2 : step;

  const shownError =
    error ||
    (socialExpired
      ? "O registo expirou. Volte a entrar com Google ou Microsoft."
      : "");

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  function handleNext(e) {
    e.preventDefault();
    setError("");

    if (!isPasswordValid(form.password)) {
      setError("A password não cumpre todos os requisitos.");
      return;
    }

    if (form.password !== form.password_confirmation) {
      setError("As palavras-passe não coincidem.");
      return;
    }
    setStep(2);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!role) {
      setError("Escolha um tipo de conta.");
      return;
    }

    const payload = socialMode
      ? { role: role }
      : {
          name: form.name,
          email: form.email,
          password: form.password,
          password_confirmation: form.password_confirmation,
          role: role,
        };
    roleFields[role].forEach((field) => {
      payload[field.name] = form[field.name];
    });

    try {
      setLoading(true);
      const result = socialMode
        ? await registerSocial(payload)
        : await register(payload);
      if (result.pending) {
        setSentTo(socialMode ? social.email : form.email);
        return;
      }

      navigate("/profiles");
    } catch (error) {
      const errors = error.response?.data?.errors;
      if (socialMode) {
        setError(
          getErrorMessage(
            error,
            "Não foi possível criar a conta. Verifique os dados.",
          ),
        );
      } else if (errors?.password) {
        setError(
          "A password escolhida não é segura. Por favor, escolha outra.",
        );
        setStep(1);
      } else {
        setError("Não foi possível criar a conta. Verifique os dados.");
      }
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  async function handleResend() {
    setResending(true);
    try {
      await resendRegistration(sentTo);
      setResent(true);
    } catch {
      setError("Não foi possível enviar o email. Tente novamente.");
    } finally {
      setResending(false);
    }
  }

  if (sentTo) {
    return (
      <div className="min-h-screen bg-surface flex items-center justify-center p-6">
        <div className="w-full max-w-md rounded-2xl bg-card border border-border p-8 text-center">
          <h1 className="font-display text-3xl text-navy">
            Confirme o seu email
          </h1>
          <p className="mt-3 text-sm text-muted">
            Enviámos um link para{" "}
            <span className="font-medium text-text">{sentTo}</span>. A conta só
            é criada depois de o abrir, nos próximos 10 minutos (pode ser noutro
            dispositivo).
          </p>
          <p className="mt-2 text-sm text-muted">
            {socialMode
              ? "Depois, volte aqui e entre com a Microsoft."
              : "Depois, volte aqui e inicie sessão."}
          </p>

          {shownError && (
            <p className="mt-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
              {shownError}
            </p>
          )}

          <div className="mt-6 flex flex-col gap-3">
            <button
              onClick={handleResend}
              disabled={resending || resent}
              className="rounded-xl border border-border px-5 py-3 font-medium text-text transition hover:bg-surface disabled:opacity-50"
            >
              {resent
                ? "Email enviado"
                : resending
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
        </div>
      </div>
    );
  }

  if (checkingSocial) {
    return (
      <div className="min-h-screen bg-surface flex items-center justify-center p-6">
        <p className="text-muted">A verificar o registo...</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-surface flex items-center justify-center p-6">
      <div className="w-full max-w-md rounded-2xl bg-card border border-border p-8">
        <h1 className="font-display text-3xl text-navy">Criar conta</h1>
        {socialMode ? (
          <p className="text-muted mb-6">
            A continuar com{" "}
            {Object.hasOwn(PROVIDER_NAMES, social.provider)
              ? PROVIDER_NAMES[social.provider]
              : "o seu fornecedor"}
            : <span className="font-medium text-text">{social.name}</span> (
            {social.email}). Falta escolher o tipo de conta.
          </p>
        ) : (
          <p className="text-muted mb-6">Passo {currentStep} de 2</p>
        )}

        {shownError && (
          <p className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {shownError}
          </p>
        )}

        {currentStep === 1 && <SocialLoginButtons />}

        {currentStep === 1 && (
          <form onSubmit={handleNext} className="flex flex-col gap-4">
            <Field
              label="Nome"
              name="name"
              value={form.name}
              onChange={handleChange}
            />
            <Field
              label="Email"
              name="email"
              type="email"
              value={form.email}
              onChange={handleChange}
            />
            <Field
              label="Password"
              name="password"
              type="password"
              value={form.password}
              onChange={handleChange}
            />

            {form.password && <PasswordRequirements password={form.password} />}

            <Field
              label="Confirmar password"
              name="password_confirmation"
              type="password"
              value={form.password_confirmation}
              onChange={handleChange}
            />

            {form.password_confirmation &&
              form.password !== form.password_confirmation && (
                <p className="text-sm text-red">As passwords não coincidem.</p>
              )}

            <button
              type="submit"
              disabled={
                !isPasswordValid(form.password) ||
                form.password !== form.password_confirmation
              }
              className="rounded-lg bg-navy px-4 py-2 font-medium text-white transition hover:bg-navy-light disabled:opacity-50"
            >
              Continuar
            </button>

            <p className="text-center text-sm text-muted">
              Já tem conta?{" "}
              <Link to="/login" className="font-medium text-navy">
                Entrar
              </Link>
            </p>
          </form>
        )}

        {currentStep === 2 && (
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div>
              <p className="mb-2 text-sm text-muted">Tipo de Conta</p>
              <div className="flex flex-col gap-2">
                {accountTypes.map((type) => (
                  <button
                    key={type.role}
                    type="button"
                    onClick={() => setRole(type.role)}
                    className={`rounded-lg border px-3 py-2 text-left transition
                        ${role === type.role ? "border-navy bg-navy/5" : "border-border hover:border-navy/40"}`}
                  >
                    <span className="font-medium text-text">{type.label}</span>
                    <span className="block text-sm text-muted">
                      {type.description}
                    </span>
                  </button>
                ))}
              </div>
            </div>

            {role &&
              roleFields[role].map((field) => (
                <Field
                  key={field.name}
                  label={field.label}
                  name={field.name}
                  type={field.type}
                  value={form[field.name]}
                  onChange={handleChange}
                />
              ))}

            <div className="flex gap-3">
              {socialMode ? (
                <Link
                  to="/register"
                  className="flex-1 rounded-lg border border-border px-4 py-2 text-center font-medium text-text transition hover:bg-surface"
                >
                  Cancelar
                </Link>
              ) : (
                <button
                  type="button"
                  onClick={() => setStep(1)}
                  className="flex-1 rounded-lg border border-border px-4 py-2 font-medium text-text transition hover:bg-surface"
                >
                  Voltar
                </button>
              )}
              <button
                type="submit"
                disabled={loading}
                className="flex-1 rounded-lg bg-navy px-4 py-2 font-medium text-white transition hover:bg-navy-light disabled:opacity-50"
              >
                {loading ? "A criar..." : "Criar conta"}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
