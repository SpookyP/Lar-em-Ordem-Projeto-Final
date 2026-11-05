import { useState, useEffect } from "react";
import { Field } from "./Field";
import {
  createOffer,
  updateOffer,
  getOfferCategories,
} from "../../services/api/offerService";
import { getErrorMessage } from "../../utils/apiError";

export function OfferForm({ offer = null, onSaved, readOnly = false }) {
  const isEdit = Boolean(offer);

  const [form, setForm] = useState({
    title: offer?.title ?? "",
    offer_category_id: offer?.category?.id ?? "",
    description: offer?.description ?? "",
    url: offer?.url ?? "",
    start_date: offer?.startDate ?? "",
    end_date: offer?.endDate ?? "",
  });
  const [categories, setCategories] = useState([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getOfferCategories()
      .then((data) => {
        if (active) setCategories(data);
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, []);

  function handleChange(e) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (
      !form.title ||
      !form.offer_category_id ||
      !form.description ||
      !form.start_date
    ) {
      setError("Preenche todos os campos obrigatórios.");
      return;
    }

    if (form.end_date && form.end_date < form.start_date) {
      setError("A data de fim não pode ser anterior à data de início.");
      return;
    }

    const payload = {
      title: form.title,
      offer_category_id: Number(form.offer_category_id),
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
      <div className="flex flex-col gap-1.5">
        <label
          htmlFor="offer_category_id"
          className="text-sm font-medium text-text"
        >
          Categoria
        </label>
        <select
          id="offer_category_id"
          name="offer_category_id"
          value={form.offer_category_id}
          onChange={handleChange}
          disabled={readOnly}
          className="rounded-lg border border-border bg-card px-3 py-2 text-sm text-text outline-none focus:border-navy disabled:bg-surface disabled:text-muted disabled:cursor-default"
        >
          <option value="">Escolhe uma categoria…</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </div>
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
