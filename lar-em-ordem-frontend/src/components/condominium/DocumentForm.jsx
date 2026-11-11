import { useState } from "react";
import { DatePicker, FileInput, SearchSelect, TextArea, TextInput } from "../form";
import { updateDocument, uploadDocument } from "../../services/api/condominiumService";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";
import { todayISO } from "../../utils/date";

const ACCEPT = ".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png";
const ALLOWED = ["application/pdf", "image/jpeg", "image/png"];
const MAX_BYTES = 15 * 1024 * 1024;

function validate(form, isEdit) {
  const errors = {};

  if (!isEdit) {
    if (!form.file) errors.file = "Escolhe o ficheiro do documento.";
    else if (!ALLOWED.includes(form.file.type)) errors.file = "O documento tem de ser PDF, JPG ou PNG.";
    else if (form.file.size > MAX_BYTES) errors.file = "O documento não pode ter mais de 15 MB.";
  }
  if (!form.title.trim()) errors.title = "Indica o título do documento.";
  if (!form.document_date) errors.document_date = "Indica a data do documento.";
  else if (form.document_date > todayISO()) {
    errors.document_date = "A data do documento não pode ser no futuro.";
  }
  if (!form.category_id) errors.condominium_document_category_id = "Escolhe a categoria do documento.";

  return errors;
}

/** Upload a document to the condominium, or correct its details (the file itself never changes). */
export function DocumentForm({ condominiumId, doc = null, categories, onSaved, onCancel }) {
  const isEdit = Boolean(doc);
  const [form, setForm] = useState({
    file: null,
    title: doc?.title ?? "",
    description: doc?.description ?? "",
    document_date: doc?.documentDate ?? todayISO(),
    category_id: doc?.category?.id ?? "",
  });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [saving, setSaving] = useState(false);

  // the API calls the category field condominium_document_category_id
  function set(name, value, errorKey = name) {
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => ({ ...current, [errorKey]: undefined }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setFormError("");

    const found = validate(form, isEdit);
    setErrors(found);
    if (Object.keys(found).length > 0) return;

    const details = {
      title: form.title.trim(),
      description: form.description.trim() || null,
      document_date: form.document_date,
      condominium_document_category_id: Number(form.category_id),
    };

    setSaving(true);
    try {
      const saved = isEdit
        ? await updateDocument(doc.id, details)
        : await uploadDocument(condominiumId, { ...details, file: form.file });
      onSaved(saved);
    } catch (err) {
      setErrors(getFieldErrors(err));
      setFormError(getErrorMessage(err, "Não foi possível guardar o documento."));
    } finally {
      setSaving(false);
    }
  }

  const categoryOptions = categories.map((c) => ({ value: c.id, label: c.name }));

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      {formError && (
        <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {formError}
        </p>
      )}

      {isEdit ? (
        <p className="rounded-xl bg-surface px-4 py-3 text-sm text-muted">
          Ficheiro: <span className="font-medium text-text">{doc.file?.name}</span>. Para
          trocar o ficheiro, apaga este documento e carrega um novo.
        </p>
      ) : (
        <FileInput
          label="Ficheiro"
          required
          accept={ACCEPT}
          value={form.file}
          onChange={(file) => set("file", file)}
          error={errors.file}
          hint="PDF, JPG ou PNG, até 15 MB."
        />
      )}

      <TextInput
        label="Título"
        required
        value={form.title}
        onChange={(value) => set("title", value)}
        error={errors.title}
        placeholder="Ata da assembleia de março"
        maxLength={255}
      />
      <div className="grid gap-4 sm:grid-cols-2">
        <SearchSelect
          label="Categoria"
          required
          options={categoryOptions}
          value={form.category_id}
          onChange={(value) => set("category_id", value, "condominium_document_category_id")}
          error={errors.condominium_document_category_id}
          placeholder="Escolher categoria..."
        />
        <DatePicker
          label="Data do documento"
          required
          max={todayISO()}
          value={form.document_date}
          onChange={(value) => set("document_date", value)}
          error={errors.document_date}
        />
      </div>
      <TextArea
        label="Descrição"
        value={form.description}
        onChange={(value) => set("description", value)}
        error={errors.description}
        maxLength={1000}
        rows={3}
        hint="Opcional."
      />

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
          {saving ? "A guardar..." : isEdit ? "Guardar alterações" : "Carregar documento"}
        </button>
      </div>
    </form>
  );
}