import { useState } from "react";
import { createPortal } from "react-dom";
import { Building, Euro, MapPin, X } from "lucide-react";
import { SearchSelect, StepProgress, TextInput } from "../form";
import { getZones } from "../../services/api/catalogService";
import {
  createCondominium,
  updateCondominium,
} from "../../services/api/condominiumService";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";
import { getCounties, getDistricts, getLocations } from "../../utils/zones";
import { useFetch } from "../../hooks/useFetch";

const STEPS = [
  {
    title: "Condomínio",
    description: "O nome do prédio e o valor do fundo comum de reserva.",
    fields: ["name", "reserve_fund"],
  },
  {
    title: "Morada",
    description: "A rua, o código postal e a porta do prédio.",
    fields: ["address.street", "address.postal_code", "address.door"],
  },
  {
    title: "Localização",
    description: "O distrito, o concelho e a localidade onde fica o prédio.",
    fields: ["address.district", "address.county", "address.location"],
  },
];

const REQUIRED = {
  name: "Indique o nome do condomínio.",
  "address.street": "Indique a rua.",
  "address.postal_code": "Indique o código postal.",
  "address.district": "Escolha o distrito.",
  "address.county": "Escolha o concelho.",
  "address.location": "Escolha a localidade.",
};

const toOptions = (names, current = "") =>
  (current && !names.includes(current) ? [current, ...names] : names).map(
    (name) => ({
      value: name,
      label: name,
    }),
  );

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

function validate(form, fields) {
  const errors = {};

  fields.forEach((key) => {
    const value = form[key.replace("address.", "")];
    if (REQUIRED[key] && !String(value ?? "").trim())
      errors[key] = REQUIRED[key];
  });

  if (
    fields.includes("reserve_fund") &&
    form.reserve_fund !== "" &&
    Number(form.reserve_fund) < 0
  ) {
    errors.reserve_fund = "O fundo comum de reserva não pode ser negativo.";
  }

  return errors;
}

function toPayload(form) {
  const payload = {
    name: form.name.trim(),
    address: {
      street: form.street.trim(),
      postal_code: form.postal_code.trim(),
      door: form.door.trim() || null,
      district: form.district,
      county: form.county,
      location: form.location.trim(),
    },
  };

  if (form.reserve_fund !== "")
    payload.reserve_fund = Number(form.reserve_fund);

  return payload;
}

/** Popup to create or edit a condominium, one step at a time. */
export function CondominiumForm({ condominium = null, onSaved, onClose }) {
  const isEdit = Boolean(condominium);
  const [form, setForm] = useState(() => initialForm(condominium));
  const [step, setStep] = useState(0);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [saving, setSaving] = useState(false);

  const { data: zones, error: zonesError } = useFetch(getZones, [], {
    errorMessage: "Não foi possível carregar os distritos.",
  });

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

    const found = validate(form, STEPS[step].fields);
    setErrors(found);
    if (Object.keys(found).length > 0) return;

    if (step < STEPS.length - 1) {
      setStep(step + 1);
      return;
    }

    setSaving(true);
    try {
      const payload = toPayload(form);
      const saved = isEdit
        ? await updateCondominium(condominium.id, payload)
        : await createCondominium(payload);
      onSaved(saved);
    } catch (err) {
      const fieldErrors = getFieldErrors(err);
      const firstStep = STEPS.findIndex((s) =>
        s.fields.some((field) => fieldErrors[field]),
      );
      setErrors(fieldErrors);
      if (firstStep >= 0) setStep(firstStep);
      setFormError(
        getErrorMessage(err, "Não foi possível guardar o condomínio."),
      );
    } finally {
      setSaving(false);
    }
  }

  const districts = zones ? toOptions(getDistricts(zones), form.district) : [];
  const counties =
    zones && form.district
      ? toOptions(getCounties(zones, form.district), form.county)
      : [];
  const locations =
    zones && form.district && form.county
      ? toOptions(
          getLocations(zones, form.district, form.county),
          form.location,
        )
      : [];

  const isLast = step === STEPS.length - 1;
  const submitLabel = saving
    ? "A guardar..."
    : !isLast
      ? "Continuar"
      : isEdit
        ? "Guardar alterações"
        : "Criar condomínio";

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60" onClick={onClose} />

      <form
        onSubmit={handleSubmit}
        noValidate
        role="dialog"
        aria-modal="true"
        aria-labelledby="condominium-form-title"
        className="relative z-10 flex max-h-full w-full max-w-lg flex-col rounded-2xl bg-card shadow-xl"
      >
        <div className="flex flex-col gap-4 p-7 pb-0">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2
                id="condominium-form-title"
                className="font-display text-2xl text-navy"
              >
                {isEdit ? "Editar condomínio" : "Novo condomínio"}
              </h2>
              <p className="mt-1 text-sm text-muted">
                {STEPS[step].description}
              </p>
            </div>
            <button
              type="button"
              onClick={onClose}
              aria-label="Fechar"
              className="rounded-lg p-1 text-muted transition hover:bg-surface hover:text-navy"
            >
              <X size={20} />
            </button>
          </div>
          <StepProgress current={step} total={STEPS.length} />
        </div>

        <div className="flex flex-col gap-4 overflow-y-auto p-7">
          <p className="text-xs text-muted">
            <span className="text-red">*</span> Campos de preenchimento
            obrigatório
          </p>
          {(formError || zonesError) && (
            <p
              role="alert"
              className="rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red"
            >
              {formError || zonesError}
            </p>
          )}

          {step === 0 && (
            <>
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
              />
            </>
          )}

          {step === 1 && (
            <>
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
                  maxLength={10}
                />
              </div>
            </>
          )}

          {step === 2 && (
            <>
              <SearchSelect
                label="Distrito"
                required
                options={districts}
                value={form.district}
                onChange={(value) => {
                  set("district", value);
                  set("county", "");
                  set("location", "");
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
                onChange={(value) => {
                  set("county", value);
                  set("location", "");
                }}
                error={errors["address.county"]}
                placeholder={
                  form.district
                    ? "Escolher concelho..."
                    : "Escolha primeiro o distrito"
                }
                disabled={!form.district}
              />
              <SearchSelect
                label="Localidade"
                required
                options={locations}
                value={form.location}
                onChange={(value) => set("location", value)}
                error={errors["address.location"]}
                placeholder={
                  form.county
                    ? "Escolher localidade..."
                    : "Escolha primeiro o concelho"
                }
                disabled={!form.county}
              />
            </>
          )}
        </div>

        <div className="flex items-center justify-end gap-2 border-t border-border px-7 py-5">
          <button
            type="button"
            onClick={step === 0 ? onClose : () => setStep(step - 1)}
            disabled={saving}
            className="rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-navy transition hover:bg-surface disabled:opacity-60"
          >
            {step === 0 ? "Cancelar" : "Voltar"}
          </button>
          <button
            type="submit"
            disabled={saving}
            className="rounded-xl bg-navy px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-light disabled:opacity-60"
          >
            {submitLabel}
          </button>
        </div>
      </form>
    </div>,
    document.body,
  );
}
