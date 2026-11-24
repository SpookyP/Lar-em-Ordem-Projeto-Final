import { useState } from "react";
import { TextInput, TextArea, SearchSelect, DatePicker } from "../form";
import {
  createOffer,
  updateOffer,
  getOfferCategories,
} from "../../services/api/offerService";
import { getErrorMessage } from "../../utils/apiError";
import { formatDate } from "../../utils/date";
import { useFetch } from "../../hooks/useFetch";

export function OfferForm({ offer = null, onSaved, readOnly = false }) {
  const isEdit = Boolean(offer);

  const [form, setForm] = useState({
    title: offer?.title ?? "",
    offer_category_id: offer?.category?.id ? String(offer.category.id) : "",
    description: offer?.description ?? "",
    url: offer?.url ?? "",
    start_date: offer?.startDate ?? "",
    end_date: offer?.endDate ?? "",
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const { data: categories } = useFetch(getOfferCategories, []);

  function setField(name, value) {
    setForm((prev) => ({ ...prev, [name]: value }));
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
      setError("Preencha todos os campos obrigatórios.");
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

  const categoryOptions = (categories ?? []).map((c) => ({
    value: String(c.id),
    label: c.name,
  }));

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      {error && !readOnly && (
        <p className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {error}
        </p>
      )}

      {!readOnly && (
        <p className="text-xs text-muted">
          <span className="text-red">*</span> Campos de preenchimento
          obrigatório
        </p>
      )}

      <TextInput
        label="Título"
        required
        value={form.title}
        onChange={(v) => setField("title", v)}
        disabled={readOnly}
      />

      <SearchSelect
        label="Categoria"
        required
        options={categoryOptions}
        value={form.offer_category_id}
        onChange={(v) => setField("offer_category_id", v)}
        placeholder="Escolha uma categoria…"
        disabled={readOnly}
      />

      <TextArea
        label="Descrição"
        required
        value={form.description}
        onChange={(v) => setField("description", v)}
        disabled={readOnly}
      />

      <TextInput
        label="Website"
        type="url"
        value={form.url}
        onChange={(v) => setField("url", v)}
        disabled={readOnly}
        placeholder="https://"
      />

      <div className="flex gap-4">
        <div className="flex-1">
          {readOnly ? (
            <TextInput
              label="Data de início"
              value={formatDate(form.start_date)}
              onChange={() => {}}
              disabled
            />
          ) : (
            <DatePicker
              label="Data de início"
              required
              value={form.start_date}
              onChange={(v) => setField("start_date", v)}
            />
          )}
        </div>
        <div className="flex-1">
          {readOnly ? (
            <TextInput
              label="Data de fim"
              value={form.end_date ? formatDate(form.end_date) : "—"}
              onChange={() => {}}
              disabled
            />
          ) : (
            <DatePicker
              label="Data de fim"
              value={form.end_date}
              onChange={(v) => setField("end_date", v)}
              min={form.start_date || undefined}
            />
          )}
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
