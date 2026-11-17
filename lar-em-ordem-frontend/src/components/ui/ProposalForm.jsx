import { useState } from "react";
import { Euro } from "lucide-react";
import { DatePicker, TextArea, TextInput, TimePicker } from "../form";
import { todayISO } from "../../utils/date";
import { getErrorMessage } from "../../utils/apiError";

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

  function setField(name, value) {
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();

    if (!form.availableFrom || !form.availableUntil) {
      setError("Indique a hora de início e a hora de fim.");
      return;
    }
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
      <TextInput
        label="Preço estimado (€)"
        icon={Euro}
        type="number"
        inputMode="decimal"
        min="0.01"
        max="9999999999.99"
        step="0.01"
        required
        value={form.value}
        onChange={(v) => setField("value", v)}
        placeholder="ex: 120"
      />

      <div className="grid gap-5 sm:grid-cols-2">
        <DatePicker
          label="Proposta válida até"
          required
          min={today}
          value={form.validity}
          onChange={(v) => setField("validity", v)}
        />
        <DatePicker
          label="Data de disponibilidade"
          required
          min={today}
          value={form.availableDate}
          onChange={(v) => setField("availableDate", v)}
        />
        <TimePicker
          label="Disponível a partir das"
          required
          value={form.availableFrom}
          onChange={(v) => setField("availableFrom", v)}
        />
        <TimePicker
          label="Disponível até às"
          required
          value={form.availableUntil}
          onChange={(v) => setField("availableUntil", v)}
        />
      </div>

      <TextArea
        label="Nota para o cliente"
        required
        maxLength={5000}
        value={form.description}
        onChange={(v) => setField("description", v)}
        placeholder="Apresente-se brevemente e indique a sua abordagem à reparação..."
      />

      {error && (
        <p role="alert" className="rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red">
          {error}
        </p>
      )}

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-xl bg-navy px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-light disabled:opacity-60"
        >
          {saving ? "A guardar..." : submitLabel}
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            disabled={saving}
            className="rounded-xl border border-border px-5 py-3 text-sm font-medium text-navy transition hover:bg-surface disabled:opacity-60"
          >
            Cancelar
          </button>
        )}
      </div>
    </form>
  );
}