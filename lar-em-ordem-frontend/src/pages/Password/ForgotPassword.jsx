import { useState } from "react";
import { Link } from "react-router-dom";
import { forgotPassword } from "../../services/api/authService";

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [status, setStatus] = useState(null);
  const [error, setError] = useState("");

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setStatus("sending");
    try {
      await forgotPassword(email);
      setStatus("sent");
    } catch (requestError) {
      if (requestError.response) {
        setStatus("sent");
      } else {
        setError("Não foi possível contactar o servidor. Tente novamente.");
        setStatus(null);
      }
    }
  }

  if (status === "sent") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-surface px-4">
        <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg text-center">
          <h1 className="font-display text-2xl text-navy">
            Verifique o seu email
          </h1>
          <p className="mt-2 text-sm text-muted">
            Se existir uma conta associada a esse email, enviámos um link para
            repor a password.
          </p>
          <Link
            to="/login"
            className="mt-6 inline-block rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            Voltar ao login
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg">
        <h1 className="font-display text-3xl text-center text-navy">
          Recuperar password
        </h1>
        <p className="mt-1 mb-6 text-sm text-center text-muted">
          Escreva o seu email e enviamos-lhe um link para repor a password.
        </p>

        {error && (
          <div className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {error}
          </div>
        )}

        <form className="flex flex-col gap-6" onSubmit={handleSubmit}>
          <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-text">Email</span>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>

          <button
            type="submit"
            disabled={status === "sending"}
            className="rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
          >
            {status === "sending" ? "A enviar..." : "Enviar link"}
          </button>

          <p className="text-center text-sm text-muted">
            <Link to="/login" className="font-medium text-navy">
              Voltar ao login
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
