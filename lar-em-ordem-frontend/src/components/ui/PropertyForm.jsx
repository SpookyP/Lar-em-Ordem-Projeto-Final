import { useState, useEffect } from "react";
import {
  getFormOptions,
  createProperty,
} from "../../services/api/propertyService";
import { useHouses } from "../../hooks/useHouses";

const EMPTY_FORM = {
  property_type_id: "",
  property_typology_id: "",
  resident_type_id: "",
  area: "",
  start_date: "",
  end_date: "",
  street: "",
  postal_code: "",
  door: "",
  fraction: "",
  county: "",
  location: "",
  district: "",
};

export function PropertyForm({ onCreated }) {
  const [options, setOptions] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);
  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");
  const { refreshHouses } = useHouses();
  const [createdSummary, setCreatedSummary] = useState(null);

  useEffect(() => {
    let active = true;
    getFormOptions().then((data) => {
      if (active) setOptions(data);
    });
    return () => {
      active = false;
    };
  }, []);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setStatus("saving");
    setErrorMsg("");

    const payload = {
      property_type_id: Number(form.property_type_id),
      property_typology_id: Number(form.property_typology_id),
      resident_type_id: Number(form.resident_type_id),
      condominium_id: null,
      area: Number(form.area),
      start_date: form.start_date,
      end_date: form.end_date || null,
      address: {
        street: form.street,
        fraction: form.fraction || null,
        postal_code: form.postal_code,
        door: form.door,
        county: form.county,
        location: form.location,
        district: form.district,
      },
    };

    try {
      const created = await createProperty(payload);
      await refreshHouses();

      setCreatedSummary({
        morada: form.fraction
          ? `${form.street}, ${form.door}, ${form.fraction}, ${form.postal_code}`
          : `${form.street}, ${form.door}, ${form.postal_code}`,
        localidade: form.location,
        area: form.area,
        tipo: options.propertyTypes.find(
          (t) => t.id === Number(form.property_type_id),
        )?.label,
        tipologia: options.typologies.find(
          (t) => t.id === Number(form.property_typology_id),
        )?.label,
        vinculo: options.residentTypes.find(
          (t) => t.id === Number(form.resident_type_id),
        )?.label,
      });

      setStatus("success");
      if (onCreated) onCreated(created);
    } catch (error) {
      setStatus("error");
      setErrorMsg(
        error.response?.data?.message ?? "Não foi possível criar a habitação.",
      );
    }
  }
  function handleCreateAnother() {
    setForm(EMPTY_FORM);
    setCreatedSummary(null);
    setStatus(null);
  }

  if (createdSummary) {
    return (
      <div className="flex flex-col gap-4">
        <h2 className="font-medium text-text">Habitação adicionada à lista</h2>
        <p className="text-sm text-green-600">
          A habitação foi criada com sucesso.
        </p>

        <div className="rounded-lg border border-border bg-surface p-4 flex flex-col gap-1 text-sm text-text">
          <p>
            <span className="text-muted">Morada: </span>
            {createdSummary.morada}
          </p>
          <p>
            <span className="text-muted">Localidade: </span>
            {createdSummary.localidade}
          </p>
          <p>
            <span className="text-muted">Tipo: </span>
            {createdSummary.tipo} · {createdSummary.tipologia}
          </p>
          <p>
            <span className="text-muted">Vínculo: </span>
            {createdSummary.vinculo}
          </p>
          <p>
            <span className="text-muted">Área: </span>
            {createdSummary.area} m²
          </p>
        </div>

        <button
          type="button"
          onClick={handleCreateAnother}
          className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark"
        >
          Criar outra habitação
        </button>
      </div>
    );
  }

  if (!options) {
    return <p className="text-sm text-muted">A carregar...</p>;
  }

  const inputClass =
    "rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy";

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      {/* Tipo de imóvel */}
      <div className="flex flex-col gap-1">
        <label htmlFor="property_type_id" className="text-sm text-muted">
          Tipo de imóvel
        </label>
        <select
          id="property_type_id"
          name="property_type_id"
          value={form.property_type_id}
          onChange={handleChange}
          required
          className={inputClass}
        >
          <option value="" disabled>
            Escolher...
          </option>
          {options.propertyTypes.map((t) => (
            <option key={t.id} value={t.id}>
              {t.label}
            </option>
          ))}
        </select>
      </div>

      {/* Tipologia */}
      <div className="flex flex-col gap-1">
        <label htmlFor="property_typology_id" className="text-sm text-muted">
          Tipologia
        </label>
        <select
          id="property_typology_id"
          name="property_typology_id"
          value={form.property_typology_id}
          onChange={handleChange}
          required
          className={inputClass}
        >
          <option value="" disabled>
            Escolher...
          </option>
          {options.typologies.map((t) => (
            <option key={t.id} value={t.id}>
              {t.label}
            </option>
          ))}
        </select>
      </div>

      {/* Vinculo */}
      <div className="flex flex-col gap-1">
        <label htmlFor="resident_type_id" className="text-sm text-muted">
          Vínculo
        </label>
        <select
          id="resident_type_id"
          name="resident_type_id"
          value={form.resident_type_id}
          onChange={handleChange}
          required
          className={inputClass}
        >
          <option value="" disabled>
            Escolher...
          </option>
          {options.residentTypes.map((t) => (
            <option key={t.id} value={t.id}>
              {t.label}
            </option>
          ))}
        </select>
      </div>

      {/* Área */}
      <div className="flex flex-col gap-1">
        <label htmlFor="area" className="text-sm text-muted">
          Área (m²)
        </label>
        <input
          id="area"
          name="area"
          type="number"
          value={form.area}
          onChange={handleChange}
          required
          placeholder="ex: 100"
          className={inputClass}
        />
      </div>

      {/* Datas */}
      <div className="flex flex-col gap-1">
        <label htmlFor="start_date" className="text-sm text-muted">
          Data de início
        </label>
        <input
          id="start_date"
          name="start_date"
          type="date"
          value={form.start_date}
          onChange={handleChange}
          required
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="end_date" className="text-sm text-muted">
          Data de fim (opcional)
        </label>
        <input
          id="end_date"
          name="end_date"
          type="date"
          value={form.end_date}
          onChange={handleChange}
          className={inputClass}
        />
      </div>

      {/* Morada */}
      <div className="flex flex-col gap-1">
        <label htmlFor="street" className="text-sm text-muted">
          Rua
        </label>
        <input
          id="street"
          name="street"
          type="text"
          value={form.street}
          onChange={handleChange}
          required
          placeholder="ex: Rua Amália Rodrigues"
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="door" className="text-sm text-muted">
          Porta
        </label>
        <input
          id="door"
          name="door"
          type="text"
          value={form.door}
          onChange={handleChange}
          required
          placeholder="ex: Nº 153"
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="fraction" className="text-sm text-muted">
          Fração (opcional)
        </label>
        <input
          id="fraction"
          name="fraction"
          type="text"
          value={form.fraction}
          onChange={handleChange}
          placeholder="ex: 2º Dto."
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="postal_code" className="text-sm text-muted">
          Código postal
        </label>
        <input
          id="postal_code"
          name="postal_code"
          type="text"
          value={form.postal_code}
          onChange={handleChange}
          required
          placeholder="ex: 4000-400"
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="location" className="text-sm text-muted">
          Localidade
        </label>
        <input
          id="location"
          name="location"
          type="text"
          value={form.location}
          onChange={handleChange}
          required
          placeholder="ex: Senhora da Hora"
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="county" className="text-sm text-muted">
          Concelho
        </label>
        <input
          id="county"
          name="county"
          type="text"
          value={form.county}
          onChange={handleChange}
          required
          placeholder="ex: Matosinhos"
          className={inputClass}
        />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="district" className="text-sm text-muted">
          Distrito
        </label>
        <input
          id="district"
          name="district"
          type="text"
          value={form.district}
          onChange={handleChange}
          required
          placeholder="ex: Porto"
          className={inputClass}
        />
      </div>

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}

      <button
        type="submit"
        disabled={status === "saving"}
        className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
      >
        {status === "saving" ? "A guardar..." : "Criar habitação"}
      </button>
    </form>
  );
}
