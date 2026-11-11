import { useRef } from "react";
import { Upload, X } from "lucide-react";
import { FormField } from "./FormField";
import { cx } from "./styles";
import { formatBytes } from "../../utils/format";

/**
 * Choose one file. Shows the name and size of the chosen file, with a button to remove it.
 * Type and size are checked by the caller (and again by the API).
 */
export function FileInput({ label, hint, error, required, value, onChange, accept, className }) {
  const inputRef = useRef(null);

  function clear() {
    onChange(null);
    if (inputRef.current) inputRef.current.value = "";
  }

  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      {value ? (
        <div
          className={cx(
            "flex items-center justify-between gap-3 rounded-xl border bg-card px-4 py-3",
            error ? "border-red" : "border-border",
          )}
        >
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-text">{value.name}</p>
            <p className="text-xs text-muted">{formatBytes(value.size)}</p>
          </div>
          <button
            type="button"
            onClick={clear}
            aria-label="Remover ficheiro"
            className="rounded-full p-1.5 text-muted transition hover:bg-surface hover:text-navy"
          >
            <X size={16} />
          </button>
        </div>
      ) : (
        <label
          className={cx(
            "flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition hover:border-navy/40",
            error ? "border-red" : "border-border",
          )}
        >
          <Upload size={22} className="text-muted" aria-hidden="true" />
          <span className="text-sm font-medium text-navy">Escolher ficheiro</span>
          <input
            ref={inputRef}
            type="file"
            accept={accept}
            className="sr-only"
            onChange={(e) => onChange(e.target.files?.[0] ?? null)}
          />
        </label>
      )}
    </FormField>
  );
}