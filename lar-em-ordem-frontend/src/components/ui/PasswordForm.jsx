import { useState } from "react";
import { updatePassword } from "../../services/api/accountService";
import { PasswordInput } from "./PasswordInput";

export function PasswordForm() {
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
    setErrorMsg("");
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

  const inputClass =
    "rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy";

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Mudar password</h2>

      <div className="flex flex-col gap-1">
        <label htmlFor="current_password" className="text-sm text-muted">
          Password atual
        </label>
        <PasswordInput
          id="current_password"
          name="current_password"
          value={form.current_password}
          onChange={handleChange}
          required
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="password" className="text-sm text-muted">
          Nova password
        </label>
        <PasswordInput
          id="password"
          name="password"
          value={form.password}
          onChange={handleChange}
          required
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="password_confirmation" className="text-sm text-muted">
          Confirmar nova password
        </label>
        <PasswordInput
          id="password_confirmation"
          name="password_confirmation"
          value={form.password_confirmation}
          onChange={handleChange}
          required
          className={inputClass}
        />
      </div>

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}
      {status === "success" && (
        <p className="text-sm text-green-600">Password alterada com sucesso.</p>
      )}

      <button
        type="submit"
        disabled={status === "saving"}
        className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
      >
        {status === "saving" ? "A guardar..." : "Guardar"}
      </button>
    </form>
  );
}
