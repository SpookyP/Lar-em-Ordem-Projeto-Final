import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import {
  updateDocument,
  getDocumentCategories,
} from "../../services/api/documentService";
import { TextInput, SearchSelect, FileInput, DatePicker } from "../form";
import { todayISO } from "../../utils/date";

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
    doc.category?.id ? String(doc.category.id) : "",
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

  const today = todayISO();

  const categoryOptions = [
    ...(doc.category && !categories.some((c) => c.id === doc.category.id)
      ? [{ value: String(doc.category.id), label: doc.category.name }]
      : []),
    ...categories.map((c) => ({ value: String(c.id), label: c.name })),
  ];

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
          <p className="text-xs text-muted">
            <span className="text-red">*</span> Campos de preenchimento
            obrigatório
          </p>

          <TextInput label="Nome" required value={name} onChange={setName} />

          <SearchSelect
            label="Categoria"
            options={categoryOptions}
            value={documentCategoryId}
            onChange={setDocumentCategoryId}
            placeholder="Escolhe uma categoria…"
          />

          <TextInput
            label="Descrição"
            value={description}
            onChange={setDescription}
          />

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <DatePicker
              label="Data de emissão"
              value={issueDate}
              onChange={setIssueDate}
              max={today}
            />
            <DatePicker
              label="Data de validade"
              value={expirationDate}
              onChange={setExpirationDate}
              min={issueDate || undefined}
            />
          </div>

          <FileInput
            label="Substituir ficheiro"
            value={file}
            onChange={setFile}
            accept=".pdf,.jpg,.jpeg,.png"
          />

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
