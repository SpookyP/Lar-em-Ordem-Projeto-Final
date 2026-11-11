import { useState } from "react";
import { SearchSelect, TextArea, TextInput } from "../form";
import { createNotice, updateNotice } from "../../services/api/condominiumService";
import { PRIORITY_OPTIONS, isNoticeCurrent } from "../../data/condominiumInfo";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";
import { todayISO } from "../../utils/date";

const MESSAGE_MAX = 5000;

// the notice stays current until the end of the chosen day (local time)
function endOfDay(day) {
  return day ? new Date(`${day}T23:59:59`).toISOString() : null;
}

// ISO date-time from the API -> "YYYY-MM-DD" in local time, for the date input
function toDay(iso) {
  return iso ? new Date(iso).toLocaleDateString("sv-SE") : "";
}

function validate(form) {
  const errors = {};
  if (!form.title.trim()) errors.title = "Indica o título do aviso.";
  if (!form.message.trim()) errors.message = "Escreve a mensagem do aviso.";
  if (form.expires_on && form.expires_on < todayISO()) {
    errors.expires_at = "A data de fim do aviso tem de ser hoje ou depois.";
  }
  return errors;
}

/** Publish a notice to the condominium, or edit one. */
export function NoticeForm({ condominiumId, notice = null, onSaved, onCancel }) {
  const isEdit = Boolean(notice);
  const [form, setForm] = useState({
    title: notice?.title ?? "",
    message: notice?.message ?? "",
    priority: notice?.priority ?? "normal",
    expires_on: toDay(notice?.expiresAt),
  });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [saving, setSaving] = useState(false);

  function set(name, value) {
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => ({ ...current, [name === "expires_on" ? "expires_at" : name]: undefined }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setFormError("");

    const found = validate(form);
    setErrors(found);
    if (Object.keys(found).length > 0) return;

    const payload = {
      title: form.title.trim(),
      message: form.message.trim(),
      priority: form.priority,
      expires_at: endOfDay(form.expires_on),
    };

    setSaving(true);
    try {
      const saved = isEdit
        ? await updateNotice(notice.id, payload)
        : await createNotice(condominiumId, payload);
      onSaved(saved);
    } catch (err) {
      setErrors(getFieldErrors(err));
      setFormError(getErrorMessage(err, "Não foi possível guardar o aviso."));
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      {formError && (
        <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {formError}
        </p>
      )}

      <TextInput
        label="Título"
        required
        value={form.title}
        onChange={(value) => set("title", value)}
        error={errors.title}
        placeholder="Corte de água"
        maxLength={255}
      />
      <TextArea
        label="Mensagem"
        required
        value={form.message}
        onChange={(value) => set("message", value)}
        error={errors.message}
        maxLength={MESSAGE_MAX}
        rows={5}
      />
      <div className="grid gap-4 sm:grid-cols-2">
        <SearchSelect
          label="Prioridade"
          options={PRIORITY_OPTIONS}
          value={form.priority}
          onChange={(value) => set("priority", value || "normal")}
          error={errors.priority}
        />
        <TextInput
          label="Em vigor até"
          type="date"
          min={todayISO()}
          value={form.expires_on}
          onChange={(value) => set("expires_on", value)}
          error={errors.expires_at}
          hint="Opcional. Sem data, fica em vigor até ser apagado."
        />
      </div>

      <p className="text-xs text-muted">
        {isEdit && isNoticeCurrent(notice)
          ? "Ao guardar, o aviso volta a ficar por ler e é enviado de novo por email aos residentes."
          : isEdit
            ? "Este aviso já expirou: só é reenviado se lhe deres uma nova data de fim."
            : "Os residentes do prédio recebem o aviso por email e na página da casa."}
      </p>

      <div className="flex items-center justify-end gap-3">
        <button
          type="button"
          onClick={onCancel}
          className="text-sm text-muted underline hover:text-navy"
        >
          Cancelar
        </button>
        <button
          type="submit"
          disabled={saving}
          className="rounded-xl bg-navy px-5 py-2.5 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
        >
          {saving ? "A guardar..." : isEdit ? "Guardar alterações" : "Publicar aviso"}
        </button>
      </div>
    </form>
  );
}