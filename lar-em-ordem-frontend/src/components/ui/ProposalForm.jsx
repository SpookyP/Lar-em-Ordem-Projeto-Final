import { useState } from "react";
import { todayISO } from "../../utils/date";
import { getErrorMessage } from "../../utils/apiError";

const inputClass =
  "rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20";

export function ProposalForm({ proposal, submitLabel, onSubmit, onCancel }) {
  const [form, setForm] = useState({
    value: proposal?.value ?? "",
    description: proposal?.description ?? "",
    validity: proposal?.validity ?? "",
    availableDate: proposal?.availableDate ?? "",
    availableFrom: proposal?.availableFrom ?? "",
    availableUntil: proposal?.availableUntil ?? "",
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const today = todayISO();

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();

    if (form.availableUntil <= form.availableFrom) {
      setError("A hora de fim tem de ser depois da hora de início.");
      return;
    }
    if (form.validity < today || form.availableDate < today) {
      setError("As datas não podem estar no passado.");
      return;
    }

    setSaving(true);
    setError("");
    try {
      await onSubmit({ ...form, description: form.description.trim() });
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível guardar a proposta."));
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-5">
      <label className="flex flex-col gap-1 text-sm text-muted">
        Preço estimado (€) *
        <input
          name="value"
          type="number"
          inputMode="decimal"
          min="0.01"
          max="9999999999.99"
          step="0.01"
          value={form.value}
          onChange={handleChange}
          placeholder="ex: 120"
          className={inputClass}
          required
        />
      </label>

      <div className="grid gap-5 sm:grid-cols-2">
        <label className="flex flex-col gap-1 text-sm text-muted">
          Proposta válida até *
          <input
            name="validity"
            type="date"
            min={today}
            value={form.validity}
            onChange={handleChange}
            className={inputClass}
            required
          />
        </label>

        <label className="flex flex-col gap-1 text-sm text-muted">
          Data de disponibilidade *
          <input
            name="availableDate"
            type="date"
            min={today}
            value={form.availableDate}
            onChange={handleChange}
            className={inputClass}
            required
          />
        </label>

        <label className="flex flex-col gap-1 text-sm text-muted">
          Disponível a partir das *
          <input
            name="availableFrom"
            type="time"
            value={form.availableFrom}
            onChange={handleChange}
            className={inputClass}
            required
          />
        </label>

        <label className="flex flex-col gap-1 text-sm text-muted">
          Disponível até às *
          <input
            name="availableUntil"
            type="time"
            value={form.availableUntil}
            onChange={handleChange}
            className={inputClass}
            required
          />
        </label>
      </div>

      <label className="flex flex-col gap-1 text-sm text-muted">
        Nota para o cliente *
        <textarea
          name="description"
          rows={4}
          maxLength={5000}
          value={form.description}
          onChange={handleChange}
          placeholder="Apresente-se brevemente e indique a sua abordagem à reparação..."
          className={`${inputClass} resize-none`}
          required
        />
      </label>

      {error && <p className="text-sm text-red font-medium">{error}</p>}

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-xl bg-navy px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-60"
        >
          {saving ? "A guardar..." : submitLabel}
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            disabled={saving}
            className="rounded-xl border border-border px-5 py-3 text-sm text-muted transition hover:text-navy disabled:opacity-60"
          >
            Cancelar
          </button>
        )}
      </div>
    </form>
  );
}