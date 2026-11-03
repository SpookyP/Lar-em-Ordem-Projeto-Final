import { useState } from "react";
import { Field } from "./Field";
import { createOffer, updateOffer } from "../../services/api/offerService";
import { getErrorMessage } from "../../utils/apiError";

export function OfferForm({ offer = null, onSaved, readOnly = false }) {
  const isEdit = Boolean(offer);

  const [form, setForm] = useState({
    title: offer?.title ?? "",
    type: offer?.type ?? "",
    description: offer?.description ?? "",
    url: offer?.url ?? "",
    start_date: offer?.startDate ?? "",
    end_date: offer?.endDate ?? "",
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  function handleChange(e) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!form.title || !form.type || !form.description || !form.start_date) {
      setError("Preenche todos os campos obrigatórios.");
      return;
    }
    if (form.end_date && form.end_date < form.start_date) {
      setError("A data de fim não pode ser anterior à data de início.");
      return;
    }

    const payload = {
      title: form.title,
      type: form.type,
      description: form.description,
      url: form.url || null,
      start_date: form.start_date,
      end_date: form.end_date || null,
    };

    try {
      setSaving(true);
      if (isEdit) {
        await updateOffer(offer.id, payload);
      } else {
        await createOffer(payload);
      }
      onSaved?.();
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível guardar a oferta."));
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      {error && !readOnly && (
        <p className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {error}
        </p>
      )}

      <Field
        label="Título"
        name="title"
        value={form.title}
        onChange={handleChange}
        disabled={readOnly}
      />
      <Field
        label="Tipo (ex.: desconto)"
        name="type"
        value={form.type}
        onChange={handleChange}
        disabled={readOnly}
      />
      <Field
        label="Descrição"
        name="description"
        type="textarea"
        value={form.description}
        onChange={handleChange}
        disabled={readOnly}
      />
      <Field
        label="Website (opcional)"
        name="url"
        type="url"
        value={form.url}
        onChange={handleChange}
        disabled={readOnly}
      />

      <div className="flex gap-4">
        <div className="flex-1">
          <Field
            label="Data de início"
            name="start_date"
            type="date"
            value={form.start_date}
            onChange={handleChange}
            disabled={readOnly}
          />
        </div>
        <div className="flex-1">
          <Field
            label="Data de fim (opcional)"
            name="end_date"
            type="date"
            value={form.end_date}
            onChange={handleChange}
            disabled={readOnly}
          />
        </div>
      </div>

      {!readOnly && (
        <button
          type="submit"
          disabled={saving}
          className="rounded-lg bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light disabled:opacity-50"
        >
          {saving ? "A guardar..." : isEdit ? "Guardar" : "Criar oferta"}
        </button>
      )}
    </form>
  );
}
