import { useEffect, useRef, useState } from "react";
import { Check, Pencil } from "lucide-react";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";

/**
 * A card with a read-only view and an edit form; each card saves on its own.
 * - view: what to show when not editing
 * - onEdit: runs when "Editar" is clicked (copy the current values into a draft)
 * - onSave: returns a promise and throws on failure (422 errors appear under the fields)
 * - children: ({ errors }) => the form fields
 */
export function EditableSection({ title, description, view, onEdit, onSave, children }) {
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState("");
  const [saved, setSaved] = useState(false);
  const savedTimer = useRef(null);

  useEffect(() => () => clearTimeout(savedTimer.current), []);

  function start() {
    onEdit?.();
    setErrors({});
    setMessage("");
    setSaved(false);
    setEditing(true);
  }

  function cancel() {
    setEditing(false);
    setErrors({});
    setMessage("");
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setMessage("");

    try {
      await onSave();
      setEditing(false);
      setSaved(true);
      clearTimeout(savedTimer.current);
      savedTimer.current = setTimeout(() => setSaved(false), 3000);
    } catch (error) {
      const fieldErrors = getFieldErrors(error);
      setErrors(fieldErrors);
      setMessage(
        Object.keys(fieldErrors).length > 0
          ? "Verifique os campos assinalados."
          : getErrorMessage(error, "Não foi possível guardar as alterações."),
      );
    } finally {
      setSaving(false);
    }
  }

  return (
    <section className="rounded-2xl border border-border bg-card p-5">
      <header className="flex items-start justify-between gap-4">
        <div>
          <h3 className="font-medium text-text">{title}</h3>
          {description && <p className="mt-0.5 text-sm text-muted">{description}</p>}
        </div>

        {!editing && (
          <div className="flex shrink-0 items-center gap-3">
            {saved && (
              <span role="status" className="inline-flex items-center gap-1 text-xs text-green">
                <Check size={14} aria-hidden="true" />
                Guardado
              </span>
            )}
            <button
              type="button"
              onClick={start}
              className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-navy transition hover:bg-surface"
            >
              <Pencil size={14} aria-hidden="true" />
              Editar
            </button>
          </div>
        )}
      </header>

      <div className="mt-4">
        {editing ? (
          <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
            {children({ errors })}

            {message && (
              <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
                {message}
              </p>
            )}

            <div className="flex justify-end gap-3 pt-1">
              <button
                type="button"
                onClick={cancel}
                disabled={saving}
                className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text transition hover:bg-surface"
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={saving}
                className="rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
              >
                {saving ? "A guardar..." : "Guardar"}
              </button>
            </div>
          </form>
        ) : (
          view
        )}
      </div>
    </section>
  );
}