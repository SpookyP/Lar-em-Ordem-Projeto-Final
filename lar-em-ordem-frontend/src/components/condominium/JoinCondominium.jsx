import { useState } from "react";
import { KeyRound } from "lucide-react";
import { TextInput } from "../form";
import { joinCondominium } from "../../services/api/condominiumService";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";

const CODE_LENGTH = 8;

/** Link a home to its building's condominium with the invite code the administrator gave. */
export function JoinCondominium({ propertyId, onJoined }) {
  const [code, setCode] = useState("");
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    const value = code.trim().toUpperCase();

    if (value.length !== CODE_LENGTH) {
      setError(`O código de convite tem ${CODE_LENGTH} caracteres.`);
      return;
    }

    setSaving(true);
    setError("");
    try {
      onJoined(await joinCondominium(value, propertyId));
    } catch (err) {
      const fields = getFieldErrors(err);
      setError(
        fields.invite_code ??
          fields.property_id ??
          getErrorMessage(err, "Não foi possível entrar no condomínio."),
      );
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4 sm:flex-row sm:items-start">
      <TextInput
        label="Código de convite"
        icon={KeyRound}
        required
        value={code}
        onChange={(value) => {
          setCode(value.toUpperCase());
          setError("");
        }}
        error={error}
        hint="Pede-o à administração do teu prédio."
        placeholder="CAPELA23"
        maxLength={CODE_LENGTH}
        autoComplete="off"
        spellCheck={false}
        className="flex-1"
      />
      <button
        type="submit"
        disabled={saving}
        className="rounded-xl bg-navy px-5 py-2.5 font-bold text-white transition hover:bg-navy-light disabled:opacity-60 sm:mt-7"
      >
        {saving ? "A entrar..." : "Entrar no condomínio"}
      </button>
    </form>
  );
}