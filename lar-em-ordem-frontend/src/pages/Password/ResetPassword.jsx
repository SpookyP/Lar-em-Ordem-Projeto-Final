import { useState } from "react";
import { useSearchParams, useNavigate, Link } from "react-router-dom";
import { PasswordInput } from "../../components/ui/PasswordInput";
import { PasswordRequirements } from "../../components/ui/PasswordRequirements";
import { isPasswordValid } from "../../utils/passwordValidation";
import { resetPassword } from "../../services/api/authService";

export default function ResetPassword() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const email = searchParams.get("email") ?? "";

  const navigate = useNavigate();

  const [form, setForm] = useState({ password: "", password_confirmation: "" });
  const [status, setStatus] = useState(null);
  const [error, setError] = useState("");

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setStatus("saving");
    try {
      await resetPassword({
        token,
        email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
      setStatus("success");
    } catch (requestError) {
      setStatus("error");
      const errors = requestError.response?.data?.errors;
      if (errors?.password) {
        setError(
          "A password escolhida não é segura. Por favor, escolha outra.",
        );
      } else if (errors?.email || errors?.token) {
        setError("O link de reposição é inválido ou já expirou. Peça um novo.");
      } else {
        setError(
          requestError.response?.data?.message ??
            "Não foi possível repor a password.",
        );
      }
    }
  }

  if (!token || !email) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-surface px-4">
        <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg text-center">
          <h1 className="font-display text-2xl text-navy">Link inválido</h1>
          <p className="mt-2 text-sm text-muted">
            Este link de reposição está incompleto ou expirou.
          </p>
          <Link
            to="/forgot-password"
            className="mt-6 inline-block rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Pedir novo link
          </Link>
        </div>
      </div>
    );
  }

  if (status === "success") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-surface px-4">
        <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg text-center">
          <h1 className="font-display text-2xl text-navy">Password alterada</h1>
          <p className="mt-2 text-sm text-muted">
            A sua password foi reposta com sucesso. Já pode iniciar sessão.
          </p>
          <button
            onClick={() => navigate("/login")}
            className="mt-6 rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Ir para o login
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg">
        <h1 className="font-display text-3xl text-center text-navy">
          Nova password
        </h1>
        <p className="mt-1 mb-6 text-sm text-center text-muted">
          A repor a password de <span className="text-text">{email}</span>
        </p>

        {error && (
          <div className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {error}
          </div>
        )}

        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-text">Nova password</span>
            <PasswordInput
              name="password"
              required
              value={form.password}
              onChange={handleChange}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>

          {form.password && <PasswordRequirements password={form.password} />}

          <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-text">
              Confirmar nova password
            </span>
            <PasswordInput
              name="password_confirmation"
              required
              value={form.password_confirmation}
              onChange={handleChange}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>

          {form.password_confirmation &&
            form.password !== form.password_confirmation && (
              <p className="text-sm text-red">As passwords não coincidem.</p>
            )}

          <button
            type="submit"
            disabled={
              status === "saving" ||
              !isPasswordValid(form.password) ||
              form.password !== form.password_confirmation
            }
            className="mt-2 rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
          >
            {status === "saving" ? "A guardar..." : "Repor password"}
          </button>
        </form>
      </div>
    </div>
  );
}
