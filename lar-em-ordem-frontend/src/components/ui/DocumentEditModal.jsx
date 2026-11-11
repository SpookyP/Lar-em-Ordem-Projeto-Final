import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import {
  updateDocument,
  getDocumentCategories,
} from "../../services/api/documentService";

export function DocumentEditModal({ doc, onClose, onSaved }) {
  const [name, setName] = useState(doc.name ?? "");
  const [description, setDescription] = useState(doc.description ?? "");
  const [expirationDate, setExpirationDate] = useState(
    doc.expirationDate ?? "",
  );
  const [issueDate, setIssueDate] = useState(doc.issueDate ?? "");
  const [file, setFile] = useState(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [documentCategoryId, setDocumentCategoryId] = useState(
    doc.category?.id ?? "",
  );
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    getDocumentCategories()
      .then(setCategories)
      .catch(() => setCategories([]));
  }, []);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    if (!name.trim()) return setError("Dá um nome ao documento.");

    setSaving(true);
    try {
      await updateDocument(doc.id, {
        name: name.trim(),
        documentCategoryId: documentCategoryId
          ? Number(documentCategoryId)
          : undefined,
        description: description.trim(),
        expirationDate: expirationDate || undefined,
        issueDate: issueDate || undefined,
        file: file || undefined,
      });
      onSaved();
      onClose();
    } catch {
      setError("Não foi possível guardar as alterações.");
    } finally {
      setSaving(false);
    }
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60" onClick={onClose} />

      <div className="relative z-10 w-full max-w-lg rounded-2xl bg-card p-6 shadow-xl">
        <div className="mb-4 flex items-start justify-between">
          <h2 className="font-display text-xl text-navy">Editar documento</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Fechar"
            className="rounded-lg p-1 text-muted transition hover:bg-surface hover:text-navy"
          >
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-navy">Nome</span>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="rounded-xl border border-border bg-surface px-3 py-2 outline-none focus:border-navy"
            />
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-navy">Categoria</span>
            <select
              value={documentCategoryId}
              onChange={(e) => setDocumentCategoryId(e.target.value)}
              className="rounded-xl border border-border bg-surface px-3 py-2 outline-none focus:border-navy"
            >
              {doc.category &&
                !categories.some((c) => c.id === doc.category.id) && (
                  <option value={doc.category.id}>{doc.category.name}</option>
                )}
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-navy">
              Descrição <span className="text-muted">(opcional)</span>
            </span>
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="rounded-xl border border-border bg-surface px-3 py-2 outline-none focus:border-navy"
            />
          </label>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-navy">
                Data de validade <span className="text-muted">(opcional)</span>
              </span>
              <input
                type="date"
                value={expirationDate}
                onChange={(e) => setExpirationDate(e.target.value)}
                className="rounded-xl border border-border bg-surface px-3 py-2 outline-none focus:border-navy"
              />
            </label>
            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-navy">
                Data de emissão <span className="text-muted">(opcional)</span>
              </span>
              <input
                type="date"
                value={issueDate}
                onChange={(e) => setIssueDate(e.target.value)}
                className="rounded-xl border border-border bg-surface px-3 py-2 outline-none focus:border-navy"
              />
            </label>
          </div>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-navy">
              Substituir ficheiro <span className="text-muted">(opcional)</span>
            </span>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
              className="text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-white hover:file:bg-navy-light"
            />
          </label>

          {error && <p className="text-sm text-red">{error}</p>}

          <div className="mt-2 flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="rounded-xl border border-border px-4 py-2 text-sm font-medium text-navy transition hover:bg-surface"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={saving}
              className="rounded-xl bg-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy-light disabled:opacity-60"
            >
              {saving ? "A guardar…" : "Guardar"}
            </button>
          </div>
        </form>
      </div>
    </div>,
    document.body,
  );
}
