import { useEffect, useState } from "react";
import { useAuth } from "../../hooks/useAuth";
import { useNavigate, Link } from "react-router-dom";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [erro, setErro] = useState("");
  const [aEnviar, setAEnviar] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setErro("");
    setAEnviar(true);
    try {
      await login(email, password);
      navigate("/dashboard");
    } catch (erroPedido) {
      const message =
        erroPedido.response?.data?.message ??
        "Não foi possível iniciar sessão. Verifique os dados e tente novamente.";
      setErro(message);
    } finally {
      setAEnviar(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-surface px-4">
      <div className="w-full max-w-sm rounded-2xl bg-card p-8 shadow-lg">
        <h1 className="font-display text-3xl text-center text-navy">Login</h1>
        <p className="mt-1 mb-6 text-sm text-center text-muted">
          Inicie sessão para continuar
        </p>

        {erro && (
          <div className="mb-4 rounded-lg bg-red-light px-3 py-2 text-sm text-red">
            {erro}
          </div>
        )}

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
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="rounded-lg border border-border px-3 py-2.5 text-sm outline-none focus:border-navy"
            />
          </label>
          <button
            type="submit"
            disabled={aEnviar}
            className="mt-2 rounded-xl bg-navy px-5 py-3 font-bold text-white transition hover:bg-navy-light"
          >
            {aEnviar ? "A entrar..." : "Entrar"}
          </button>
          <p className="mt-4 text-center text-sm text-muted">
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
