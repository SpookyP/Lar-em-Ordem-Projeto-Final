import { useState } from "react";
import { updatePassword } from "../../services/api/accountService";
import { PasswordField } from "../form";
import { isPasswordValid } from "../../utils/passwordValidation";
import { PasswordRequirements } from "./PasswordRequirements";

export function PasswordForm() {
  const [form, setForm] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });

  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");

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
    } catch (error) {
      setStatus("error");
      const errors = error.response?.data?.errors;
      if (errors?.password) {
        setErrorMsg(
          "A password escolhida não é segura. Por favor, escolha outra.",
        );
      } else {
        setErrorMsg(
          error.response?.data?.message ??
            "Não foi possível alterar a password.",
        );
      }
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Mudar password</h2>

      <PasswordField
        label="Password atual"
        name="current_password"
        required
        value={form.current_password}
        onChange={(v) => setForm((prev) => ({ ...prev, current_password: v }))}
        autoComplete="current-password"
      />

      <PasswordField
        label="Nova password"
        name="password"
        required
        value={form.password}
        onChange={(v) => setForm((prev) => ({ ...prev, password: v }))}
        autoComplete="new-password"
      />

      {form.password && <PasswordRequirements password={form.password} />}

      <PasswordField
        label="Confirmar nova password"
        name="password_confirmation"
        required
        value={form.password_confirmation}
        onChange={(v) =>
          setForm((prev) => ({ ...prev, password_confirmation: v }))
        }
        autoComplete="new-password"
      />

      {form.password_confirmation &&
        form.password !== form.password_confirmation && (
          <p className="text-sm text-red-600">As passwords não coincidem.</p>
        )}

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}
      {status === "success" && (
        <p className="text-sm text-green-600">Password alterada com sucesso.</p>
      )}

      <button
        type="submit"
        disabled={
          status === "saving" ||
          !isPasswordValid(form.password) ||
          form.password !== form.password_confirmation
        }
        className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
      >
        {status === "saving" ? "A guardar..." : "Guardar"}
      </button>
    </form>
  );
}
