import { useRef, useState } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import {
  extractDocumentData,
  uploadDocument,
} from "../../services/api/documentService";
import { getErrorMessage } from "../../utils/apiError";
import { TextInput, SearchSelect, FileInput, DatePicker } from "../form";

export function DocumentUploadModal({
  propertyId,
  categories = [],
  initialCategoryId,
  initialName = "",
  onClose,
  onUploaded,
}) {
  const [file, setFile] = useState(null);
  const [name, setName] = useState(initialName);
  const [documentCategoryId, setDocumentCategoryId] = useState(
    initialCategoryId ? String(initialCategoryId) : "",
  );
  const [description, setDescription] = useState("");
  const [expirationDate, setExpirationDate] = useState("");
  const [issueDate, setIssueDate] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [reading, setReading] = useState(false);
  const lastFile = useRef(null);

  // Ao escolher um PDF, lê-o logo e preenche o nome e as datas 
  async function handleFile(selected) {
    setFile(selected);
    lastFile.current = selected;

    const isPdf =
      selected &&
      (selected.type === "application/pdf" || /\.pdf$/i.test(selected.name));
    if (!isPdf) {
      setReading(false);
      return;
    }

    setReading(true);
    try {
      const data = await extractDocumentData(propertyId, selected);
      if (lastFile.current !== selected) return;

      if (data.suggestedTitle) {
        setName((current) => (current.trim() ? current : data.suggestedTitle));
      }
      if (data.issueDate) {
        setIssueDate((current) => current || data.issueDate);
      }
      if (data.expirationDate) {
        setExpirationDate((current) => current || data.expirationDate);
      }
    } catch {
      // A leitura automática é só uma ajuda: se falhar, os campos preenchem-se à mão
    } finally {
      if (lastFile.current === selected) setReading(false);
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!file) return setError("Escolha um ficheiro.");
    if (!name.trim()) return setError("Dê um nome ao documento.");
    if (!documentCategoryId) return setError("Escolha uma categoria.");

    setSaving(true);
    try {
      await uploadDocument(propertyId, {
        name: name.trim(),
        documentCategoryId: Number(documentCategoryId),
        description: description.trim() || undefined,
        file,
        expirationDate: expirationDate || undefined,
        issueDate: issueDate || undefined,
      });
      onUploaded();
      onClose();
    } catch (err) {
      setError(
        getErrorMessage(
          err,
          "Não foi possível carregar o documento. Tente novamente.",
        ),
      );
    } finally {
      setSaving(false);
    }
  }

  const categoryOptions = categories.map((c) => ({
    value: String(c.id),
    label: c.name,
  }));

  const today = new Date().toLocaleDateString("sv-SE");

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60" onClick={onClose} />

      <div className="relative z-10 w-full max-w-lg rounded-2xl bg-card p-6 shadow-xl">
        <div className="mb-4 flex items-start justify-between">
          <h2 className="font-display text-xl text-navy">Carregar documento</h2>
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

          <FileInput
            label="Ficheiro"
            required
            value={file}
            onChange={handleFile}
            accept=".pdf,.jpg,.jpeg,.png"
            hint={reading ? "A ler o documento…" : undefined}
          />

          <TextInput
            label="Nome"
            required
            value={name}
            onChange={setName}
            placeholder="Ex.: Seguro Multirriscos"
          />

          <SearchSelect
            label="Categoria"
            required
            options={categoryOptions}
            value={documentCategoryId}
            onChange={setDocumentCategoryId}
            placeholder="Escolha uma categoria…"
          />

          <TextInput
            label="Descrição"
            value={description}
            onChange={setDescription}
            placeholder="Ex.: Fidelidade - Apólice 123456"
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
              disabled={saving || reading}
              className="rounded-xl bg-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy-light disabled:opacity-60"
            >
              {saving ? "A carregar…" : "Carregar"}
            </button>
          </div>
        </form>
      </div>
    </div>,
    document.body,
  );
}