import { useState } from "react";
import { KeyRound } from "lucide-react";
import { TextInput } from "../form";
import { claimCondominium } from "../../services/api/condominiumService";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";

const CODE_LENGTH = 12;

/** Accept a condominium from its current administrator with the transfer code. */
export function ClaimCondominium({ onClaimed, onCancel }) {
  const [code, setCode] = useState("");
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    const value = code.trim().toUpperCase();

    if (value.length !== CODE_LENGTH) {
      setError(`O código de transferência tem ${CODE_LENGTH} caracteres.`);
      return;
    }

    setSaving(true);
    setError("");
    try {
      onClaimed(await claimCondominium(value));
    } catch (err) {
      setError(
        getFieldErrors(err).transfer_code ??
          getErrorMessage(err, "Não foi possível receber o condomínio."),
      );
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      <TextInput
        label="Código de transferência"
        icon={KeyRound}
        required
        value={code}
        onChange={(value) => {
          setCode(value.toUpperCase());
          setError("");
        }}
        error={error}
        hint="O código que o administrador atual te deu. É válido durante 7 dias."
        maxLength={CODE_LENGTH}
        autoComplete="off"
        spellCheck={false}
      />
      <div className="flex items-center justify-end gap-3">
        <button type="button" onClick={onCancel} className="text-sm text-muted underline hover:text-navy">
          Cancelar
        </button>
        <button
          type="submit"
          disabled={saving}
          className="rounded-xl bg-navy px-5 py-2.5 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
        >
          {saving ? "A receber..." : "Receber condomínio"}
        </button>
      </div>
    </form>
  );
}