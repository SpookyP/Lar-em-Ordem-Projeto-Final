import { useState } from "react";
import {
  getFormOptions,
  createProperty,
  updateProperty,
} from "../../services/api/propertyService";
import { useHouses } from "../../hooks/useHouses";
import { ZoneSelect } from "../ui/ZoneSelect";
import { TextInput, SearchSelect, DatePicker, StepProgress } from "../form";
import { todayISO } from "../../utils/date";
import { getErrorMessage } from "../../utils/apiError";
import { useFetch } from "../../hooks/useFetch";
import { LoadingState } from "../ui/LoadingState";

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

const STEP1_FIELDS = [
  "property_type_id",
  "property_typology_id",
  "resident_type_id",
  "area",
  "start_date",
];

const STEP2_FIELDS = [
  "street",
  "door",
  "postal_code",
  "district",
  "county",
  "location",
];

function propertyToForm(p) {
  return {
    property_type_id: p.propertyTypeId ? String(p.propertyTypeId) : "",
    property_typology_id: p.typologyId ? String(p.typologyId) : "",
    resident_type_id: p.residentTypeId ? String(p.residentTypeId) : "",
    area: p.area ?? "",
    start_date: (p.startDate ?? "").slice(0, 10),
    end_date: (p.endDate ?? "").slice(0, 10),
    street: p.address?.street ?? "",
    postal_code: p.address?.postalCode ?? "",
    door: p.address?.door ?? "",
    fraction: p.address?.fraction ?? "",
    county: p.address?.county ?? "",
    location: p.address?.location ?? "",
    district: p.address?.district ?? "",
  };
}

export function PropertyForm({
  onCreated,
  onUpdated,
  secondaryAction,
  property,
}) {
  const [form, setForm] = useState(() =>
    property ? propertyToForm(property) : EMPTY_FORM,
  );
  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");
  const { refreshHouses } = useHouses();
  const [createdSummary, setCreatedSummary] = useState(null);
  const isEditing = Boolean(property);
  const [stepIndex, setStepIndex] = useState(0);

  const { data: options } = useFetch(getFormOptions, []);

  function setField(name, value) {
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  const tenantTypeId =
    options?.residentTypes.find((t) => /arrenda|inquil/i.test(t.label))?.id ??
    null;
  const isTenant =
    tenantTypeId != null && String(tenantTypeId) === form.resident_type_id;

  function changeBond(v) {
    const tenant = tenantTypeId != null && String(tenantTypeId) === v;
    setForm((prev) => ({
      ...prev,
      resident_type_id: v,
      //só o arrendatário indica datas. o proprietário fica com a data de hoje
      start_date: tenant ? "" : todayISO(),
      end_date: "",
    }));
  }

  function isMissing(fields) {
    return fields.some((name) => !form[name]);
  }

  function goBack() {
    setStatus(null);
    setErrorMsg("");
    setStepIndex(0);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setErrorMsg("");

    if (stepIndex === 0) {
      if (isMissing(STEP1_FIELDS)) {
        setErrorMsg("Preencha todos os campos obrigatórios.");
        setStatus("error");
        return;
      }
      setStatus(null);
      setStepIndex(1);
      return;
    }

    if (isMissing(STEP1_FIELDS)) {
      setErrorMsg("Preencha todos os campos obrigatórios.");
      setStatus("error");
      setStepIndex(0);
      return;
    }
    if (isMissing(STEP2_FIELDS)) {
      setErrorMsg("Preencha todos os campos obrigatórios.");
      setStatus("error");
      return;
    }

    setStatus("saving");

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
      if (isEditing) {
        const updated = await updateProperty(property.id, payload);
        await refreshHouses();
        setStatus("success");
        if (onUpdated) onUpdated(updated);
      } else {
        const created = await createProperty(payload);
        await refreshHouses();

        setCreatedSummary({
          address: form.fraction
            ? `${form.street}, ${form.door}, ${form.fraction}, ${form.postal_code}`
            : `${form.street}, ${form.door}, ${form.postal_code}`,
          location: form.location,
          area: form.area,
          type: options.propertyTypes.find(
            (t) => t.id === Number(form.property_type_id),
          )?.label,
          typology: options.typologies.find(
            (t) => t.id === Number(form.property_typology_id),
          )?.label,
          bond: options.residentTypes.find(
            (t) => t.id === Number(form.resident_type_id),
          )?.label,
        });

        setStatus("success");
        if (onCreated) onCreated(created);
      }
    } catch (error) {
      setStatus("error");
      setErrorMsg(
        getErrorMessage(
          error,
          isEditing
            ? "Não foi possível guardar as alterações."
            : "Não foi possível criar a habitação.",
        ),
      );
    }
  }

  function handleCreateAnother() {
    setForm(EMPTY_FORM);
    setCreatedSummary(null);
    setStatus(null);
    setStepIndex(0);
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
            {createdSummary.address}
          </p>
          <p>
            <span className="text-muted">Localidade: </span>
            {createdSummary.location}
          </p>
          <p>
            <span className="text-muted">Tipo: </span>
            {createdSummary.type} · {createdSummary.typology}
          </p>
          <p>
            <span className="text-muted">Vínculo: </span>
            {createdSummary.bond}
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
    return <LoadingState />;
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      <StepProgress current={stepIndex} total={2} />
      <p className="text-xs text-muted">
        <span className="text-red">*</span> Campos de preenchimento obrigatório
      </p>

      {stepIndex === 0 ? (
        <>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <SearchSelect
              label="Tipo de imóvel"
              required
              options={options.propertyTypes.map((t) => ({
                value: String(t.id),
                label: t.label,
              }))}
              value={form.property_type_id}
              onChange={(v) => setField("property_type_id", v)}
            />

            <SearchSelect
              label="Tipologia"
              required
              options={options.typologies.map((t) => ({
                value: String(t.id),
                label: t.label,
              }))}
              value={form.property_typology_id}
              onChange={(v) => setField("property_typology_id", v)}
            />
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <SearchSelect
              label="Vínculo"
              required
              options={options.residentTypes.map((t) => ({
                value: String(t.id),
                label: t.label,
              }))}
              value={form.resident_type_id}
              onChange={changeBond}
            />

            <TextInput
              label="Área (m²)"
              type="number"
              required
              value={form.area}
              onChange={(v) => setField("area", v)}
              onWheel={(e) => e.currentTarget.blur()}
              placeholder="ex: 100"
            />
          </div>
          {isTenant && (
            <div className="flex flex-col gap-2">
              <p className="rounded-xl bg-surface px-4 py-3 text-sm text-muted">
                As datas referem-se ao período de arrendamento.
              </p>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <DatePicker
                  label="Início do arrendamento"
                  required
                  value={form.start_date}
                  onChange={(v) => setField("start_date", v)}
                  hint="Data de início do contrato."
                />
                <DatePicker
                  label="Fim do arrendamento"
                  value={form.end_date}
                  onChange={(v) => setField("end_date", v)}
                  min={form.start_date || undefined}
                  hint="Vazio se for por tempo indeterminado."
                />
              </div>
            </div>
          )}
        </>
      ) : (
        <>
          <TextInput
            label="Rua"
            required
            value={form.street}
            onChange={(v) => setField("street", v)}
            placeholder="ex: Rua Amália Rodrigues"
          />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <TextInput
              label="Porta"
              required
              value={form.door}
              onChange={(v) => setField("door", v)}
              placeholder="ex: 153"
            />
            <TextInput
              label="Fração"
              value={form.fraction}
              onChange={(v) => setField("fraction", v)}
              placeholder="ex: 2º Dto."
            />
          </div>

          <TextInput
            label="Código postal"
            required
            value={form.postal_code}
            onChange={(v) => setField("postal_code", v)}
            placeholder="ex: 4000-400"
          />

          <ZoneSelect
            district={form.district}
            county={form.county}
            location={form.location}
            onChange={(zone) => setForm((prev) => ({ ...prev, ...zone }))}
          />
        </>
      )}

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}

      <div className="flex gap-3 self-start">
        {stepIndex === 1 && (
          <button
            type="button"
            onClick={goBack}
            className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text transition hover:bg-surface"
          >
            Voltar
          </button>
        )}
        <button
          type="submit"
          disabled={status === "saving"}
          className="rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
        >
          {stepIndex === 0
            ? "Continuar"
            : status === "saving"
              ? "A guardar..."
              : isEditing
                ? "Guardar alterações"
                : "Criar habitação"}
        </button>
        {secondaryAction}
      </div>
    </form>
  );
}
