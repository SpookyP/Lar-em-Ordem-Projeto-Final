import { useEffect, useState } from "react";
import { Building, Euro, MapPin } from "lucide-react";
import { SearchSelect, TextInput } from "../form";
import { getZones } from "../../services/api/catalogService";
import {
  createCondominium,
  updateCondominium,
} from "../../services/api/condominiumService";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";
import { getCounties, getDistricts } from "../../utils/zones";

const toOptions = (names) => names.map((name) => ({ value: name, label: name }));

function initialForm(condominium) {
  const address = condominium?.address ?? {};
  return {
    name: condominium?.name ?? "",
    reserve_fund: condominium?.reserveFund ?? "",
    street: address.street ?? "",
    postal_code: address.postal_code ?? "",
    door: address.door ?? "",
    district: address.district ?? "",
    county: address.county ?? "",
    location: address.location ?? "",
  };
}

// same rules as the API, so most mistakes show up before sending; the API still checks everything
function validate(form) {
  const errors = {};
  const required = {
    name: "Indica o nome do condomínio.",
    "address.street": "Indica a rua.",
    "address.postal_code": "Indica o código postal.",
    "address.district": "Escolhe o distrito.",
    "address.county": "Escolhe o concelho.",
    "address.location": "Indica a localidade.",
  };

  Object.entries(required).forEach(([key, message]) => {
    const value = form[key.replace("address.", "")];
    if (!String(value ?? "").trim()) errors[key] = message;
  });

  if (form.reserve_fund !== "" && Number(form.reserve_fund) < 0) {
    errors.reserve_fund = "O fundo comum de reserva não pode ser negativo.";
  }

  return errors;
}

function toPayload(form) {
  const payload = {
    name: form.name.trim(),
    // district and county always go together: the API checks the pair against the zones
    address: {
      street: form.street.trim(),
      postal_code: form.postal_code.trim(),
      door: form.door.trim() || null,
      district: form.district,
      county: form.county,
      location: form.location.trim(),
    },
  };

  if (form.reserve_fund !== "") payload.reserve_fund = Number(form.reserve_fund);

  return payload;
}

/** Create or edit a condominium (name, reserve fund and the building's address). */
export function CondominiumForm({ condominium = null, onSaved }) {
  const isEdit = Boolean(condominium);
  const [form, setForm] = useState(() => initialForm(condominium));
  const [zones, setZones] = useState(null);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let active = true;
    getZones()
      .then((data) => active && setZones(data))
      .catch(() => active && setFormError("Não foi possível carregar os distritos."));
    return () => {
      active = false;
    };
  }, []);

  function set(name, value) {
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => {
      const next = { ...current };
      delete next[name];
      delete next[`address.${name}`];
      return next;
    });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setFormError("");

    const found = validate(form);
    setErrors(found);
    if (Object.keys(found).length > 0) return;

    setSaving(true);
    try {
      const payload = toPayload(form);
      const saved = isEdit
        ? await updateCondominium(condominium.id, payload)
        : await createCondominium(payload);
      onSaved?.(saved);
    } catch (err) {
      setErrors(getFieldErrors(err));
      setFormError(getErrorMessage(err, "Não foi possível guardar o condomínio."));
    } finally {
      setSaving(false);
    }
  }

  const districts = zones ? toOptions(getDistricts(zones)) : [];
  const counties = zones && form.district ? toOptions(getCounties(zones, form.district)) : [];

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-6">
      {formError && (
        <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {formError}
        </p>
      )}

      <section className="flex flex-col gap-4">
        <h2 className="font-medium text-navy">Condomínio</h2>
        <TextInput
          label="Nome"
          icon={Building}
          required
          value={form.name}
          onChange={(value) => set("name", value)}
          error={errors.name}
          placeholder="Edifício Mar"
          maxLength={255}
        />
        <TextInput
          label="Fundo comum de reserva"
          icon={Euro}
          type="number"
          inputMode="decimal"
          min={0}
          step="0.01"
          value={form.reserve_fund}
          onChange={(value) => set("reserve_fund", value)}
          error={errors.reserve_fund}
          hint="Opcional. Valor atual do fundo, em euros."
        />
      </section>

      <section className="flex flex-col gap-4">
        <h2 className="font-medium text-navy">Morada do prédio</h2>
        <TextInput
          label="Rua"
          icon={MapPin}
          required
          value={form.street}
          onChange={(value) => set("street", value)}
          error={errors["address.street"]}
          autoComplete="street-address"
          maxLength={255}
        />
        <div className="grid gap-4 sm:grid-cols-2">
          <TextInput
            label="Código postal"
            required
            value={form.postal_code}
            onChange={(value) => set("postal_code", value)}
            error={errors["address.postal_code"]}
            placeholder="4450-073"
            autoComplete="postal-code"
            maxLength={20}
          />
          <TextInput
            label="Porta"
            value={form.door}
            onChange={(value) => set("door", value)}
            error={errors["address.door"]}
            placeholder="Nº 100"
            hint="Opcional."
            maxLength={10}
          />
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          <SearchSelect
            label="Distrito"
            required
            options={districts}
            value={form.district}
            onChange={(value) => {
              set("district", value);
              set("county", "");
            }}
            error={errors["address.district"]}
            placeholder={zones ? "Escolher distrito..." : "A carregar..."}
            disabled={!zones}
          />
          <SearchSelect
            label="Concelho"
            required
            options={counties}
            value={form.county}
            onChange={(value) => set("county", value)}
            error={errors["address.county"]}
            placeholder={form.district ? "Escolher concelho..." : "Escolhe primeiro o distrito"}
            disabled={!form.district}
          />
        </div>
        <TextInput
          label="Localidade"
          required
          value={form.location}
          onChange={(value) => set("location", value)}
          error={errors["address.location"]}
          hint="Freguesia ou localidade do prédio."
          maxLength={100}
        />
      </section>

      <button
        type="submit"
        disabled={saving}
        className="self-end rounded-xl bg-navy px-5 py-2.5 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
      >
        {saving ? "A guardar..." : isEdit ? "Guardar alterações" : "Criar condomínio"}
      </button>
    </form>
  );
}