import { useState } from "react";
import { ExternalLink, FileImage, FileText } from "lucide-react";
import { getDocumentLink } from "../../services/api/condominiumService";
import { formatDay } from "../../utils/date";
import { formatBytes } from "../../utils/format";
import { getErrorMessage } from "../../utils/apiError";

/**
 * One condominium document: title, category, date, size and an "Abrir" button that asks the API
 * for a temporary link. `children` adds the administrator's actions below it.
 */
export function DocumentCard({ doc, children }) {
  const [opening, setOpening] = useState(false);
  const [error, setError] = useState("");
  const Icon = doc.file?.mimeType?.startsWith("image/") ? FileImage : FileText;

  async function open() {
    // the tab opens right away, inside the click, so pop-up blockers allow it
    const tab = window.open("", "_blank");
    setOpening(true);
    setError("");
    try {
      const url = await getDocumentLink(doc.id);
      if (tab) {
        tab.opener = null;
        tab.location.href = url;
      } else {
        window.location.href = url;
      }
    } catch (err) {
      tab?.close();
      setError(getErrorMessage(err, "Não foi possível abrir o documento."));
    } finally {
      setOpening(false);
    }
  }

  return (
    <article className="flex flex-col gap-3 rounded-2xl bg-card p-5 shadow-sm">
      <div className="flex items-start gap-3">
        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-navy/10 text-navy">
          <Icon size={20} aria-hidden="true" />
        </span>
        <div className="min-w-0 flex-1">
          <h2 className="font-medium text-navy">{doc.title}</h2>
          <p className="text-xs text-muted">
            {[doc.category?.name, formatDay(doc.documentDate), formatBytes(doc.file?.size)]
              .filter(Boolean)
              .join(" · ")}
          </p>
        </div>
        <button
          type="button"
          onClick={open}
          disabled={opening}
          className="flex shrink-0 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-sm text-navy transition hover:bg-surface disabled:opacity-60"
        >
          <ExternalLink size={15} />
          {opening ? "A abrir..." : "Abrir"}
        </button>
      </div>

      {doc.description && <p className="text-sm text-text">{doc.description}</p>}

      {error && (
        <p role="alert" className="text-sm text-red">
          {error}
        </p>
      )}

      {children}
    </article>
  );
}