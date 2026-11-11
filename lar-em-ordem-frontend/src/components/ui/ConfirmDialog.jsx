import { createPortal } from "react-dom";

export function ConfirmDialog({
  title,
  message,
  confirmLabel = "Confirmar",
  cancelLabel = "Cancelar",
  loadingLabel = "A processar…",
  danger = false,
  loading = false,
  onConfirm,
  onCancel,
}) {
  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60" onClick={onCancel} />

      <div className="relative z-10 w-full max-w-sm rounded-2xl bg-card p-6 shadow-xl">
        <h2 className="font-display text-xl text-navy">{title}</h2>
        {message && <p className="mt-2 text-sm text-muted">{message}</p>}

        <div className="mt-6 flex justify-end gap-2">
          <button
            type="button"
            onClick={onCancel}
            disabled={loading}
            className="rounded-xl border border-border px-4 py-2 text-sm font-medium text-navy transition hover:bg-surface disabled:opacity-60"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={loading}
            className={`rounded-xl px-4 py-2 text-sm font-semibold text-white transition disabled:opacity-60 ${
              danger ? "bg-red hover:bg-red/90" : "bg-navy hover:bg-navy-light"
            }`}
          >
            {loading ? loadingLabel : confirmLabel}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  );
}
