import { useState } from "react";
import { updatePassword } from "../../services/api/accountService";

export default function Definicoes() {
  const [form, setForm] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });

  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setStatus("saving");
    setErrorMag("");
    try {
      await updatePassword(form);
      setStatus("success");
      setForm({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
    } catch (erro) {
      setStatus("error");
      setErrorMsg(
        erro.response?.data?.message ?? "Não foi possível alterar a password.",
      );
    }
  }

  return (
    <div className="p-6 max-w-2xl mx-auto">
      <h1 className="font-display text-3xl text-navy mb-6">
        Definições de conta
      </h1>

      <div className="flex flex-col gap-6">
        {/* Mudar pass */}
        <section className="rounded-2xl bg-card border border-border p-6">
          <h2 className="font-medium text-text mb-4">Mudar password</h2>

          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
              <label htmlFor="current_password" className="text-sm text-muted">
                Password atual
              </label>
              <input
                id="current_password"
                name="current_password"
                type="password"
                value={form.current_password}
                onChange={handleChange}
                required
                className="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy"
              />
            </div>

            <div className="flex flex-col gap-1">
              <label htmlFor="password" className="text-sm text-muted">
                Nova password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                value={form.password}
                onChange={handleChange}
                required
                className="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy"
              />
            </div>

            <div className="flex flex-col gap-1">
              <label
                htmlFor="password_confirmation"
                className="text-sm text-muted"
              >
                Confirmar nova password
              </label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                value={form.password_confirmation}
                onChange={handleChange}
                required
                className="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy"
              />
            </div>

            {status === "error" && (
              <p className="text-sm text-red-600">{errorMsg}</p>
            )}
            {status === "success" && (
              <p className="text-sm text-green-600">
                Password alterada com sucesso.
              </p>
            )}
            <button
              type="submit"
              disabled={status === "saving"}
              className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
            >
              {status === "saving" ? "A guardar..." : "Guardar"}
            </button>
          </form>
        </section>
      </div>
    </div>
  );
}
