import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { accountTypes, roleFields } from "../../data/registerFields";
import { useAuth } from "../../hooks/useAuth";

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

export default function Registar() {
  const [form, setForm] = useState(INITIAL_FORM);
  const [error, setError] = useState("");
  const [step, setStep] = useState(1);
  const [role, setRole] = useState("");
  const [loading, setLoading] = useState(false);

  const { register } = useAuth();

  const navigate = useNavigate();

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  function handleNext(e) {
    e.preventDefault();
    setError("");
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

    const payload = {
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
      await register(payload);
      navigate("/dashboard");
    } catch (error) {
      setError("Não foi possível criar a conta. Verifique os dados.");
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-surface flex items-center justify-center p-6">
      <div className="w-full max-w-md rounded-2xl bg-card border border-border p-8">
        <h1 className="font-display text-3xl text-navy">Criar conta</h1>
        <p className="text-muted mb-6">Passo {step} de 2</p>

        {error && (
          <p className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {error}
          </p>
        )}

        {step === 1 && (
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
            <Field
              label="Confirmar password"
              name="password_confirmation"
              type="password"
              value={form.password_confirmation}
              onChange={handleChange}
            />

            <button
              type="submit"
              className="rounded-lg bg-navy px-4 py-2 font-medium text-white transition hover:bg-navy-light"
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

        {step === 2 && (
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
              <button
                type="button"
                onClick={() => setStep(1)}
                className="flex-1 rounded-lg border border-border px-4 py-2 font-medium text-text transition hover:bg-surface"
              >
                Voltar
              </button>
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

function Field({ label, name, value, onChange, type = "text" }) {
  const shared =
    "rounded-lg border border-border px-3 py-2 text-text outline-none focus:border-navy";
  return (
    <label className="flex flex-col gap-1">
      <span className="text-sm text-text">{label}</span>
      {type === "textarea" ? (
        <textarea
          name={name}
          value={value}
          onChange={onChange}
          rows={3}
          className={shared}
        />
      ) : (
        <input
          name={name}
          type={type}
          value={value}
          onChange={onChange}
          className={shared}
        />
      )}
    </label>
  );
}
