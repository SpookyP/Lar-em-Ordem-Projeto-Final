import { useEffect, useMemo, useState } from "react";
import { Plus, X } from "lucide-react";
import { getZones } from "../../services/api/catalogService";
import { getCategories } from "../../services/api/assistanceService";
import { getCounties, getDistricts, zoneIdsFor } from "../../utils/zones";
import { MultiSearchSelect, SearchSelect, TextInput } from "../form";
import { chipClass } from "../form/styles";

export const EMPTY_COVERAGE = {
  zone_ids: [],
  service_specialty_ids: [],
  custom_specialties: [],
};

/** The three coverage keys the API expects, ready to merge into a request payload. */
export function coverageToPayload(coverage) {
  return {
    zone_ids: coverage.zone_ids,
    service_specialty_ids: coverage.service_specialty_ids,
    custom_specialties: coverage.custom_specialties ?? [],
  };
}

const DISTRICT_PREFIX = "district:";

function useCatalog(load) {
  const [state, setState] = useState({ data: null, failed: false });

  useEffect(() => {
    let active = true;
    load()
      .then((data) => active && setState({ data, failed: false }))
      .catch(() => active && setState({ data: null, failed: true }));
    return () => {
      active = false;
    };
  }, [load]);

  return state;
}

function countyKey(zone) {
  return `${zone.district}|${zone.county}`;
}

// matching works per county, so the picker offers counties (plus a "whole district" shortcut)
// and turns them back into the zone ids the API stores
function useCountyIndex(zones) {
  return useMemo(() => {
    if (!zones) return null;

    const counties = new Map(); // "district|county" -> { district, county, ids }
    const options = [];

    getDistricts(zones).forEach((district) => {
      options.push({
        value: DISTRICT_PREFIX + district,
        label: `Todo o distrito de ${district}`,
        description: "Distrito",
      });

      getCounties(zones, district).forEach((county) => {
        const key = `${district}|${county}`;
        counties.set(key, { district, county, ids: zoneIdsFor(zones, district, county) });
        options.push({ value: key, label: county, description: district });
      });
    });

    return {
      counties,
      keyByZoneId: new Map(zones.map((z) => [z.id, countyKey(z)])),
      options,
    };
  }, [zones]);
}

function selectedCountyKeys(index, zoneIds) {
  return [...new Set(zoneIds.map((id) => index.keyByZoneId.get(id)).filter(Boolean))];
}

function CatalogMessage({ failed, what }) {
  return failed ? (
    <p className="text-sm text-red">Não foi possível carregar {what}.</p>
  ) : (
    <p className="text-sm text-muted">A carregar {what}...</p>
  );
}

/** Zones picker. value = zone ids; onChange receives zone ids. */
export function ZoneCoverageField({
  value,
  onChange,
  error,
  label = "Zonas de atuação",
  hint = "Escolha os concelhos onde trabalha. Se não escolher nenhum, vê pedidos de todo o país.",
}) {
  const { data: zones, failed } = useCatalog(getZones);
  const index = useCountyIndex(zones);

  if (!index) return <CatalogMessage failed={failed} what="as zonas" />;

  function handleChange(keys) {
    const chosen = new Set();
    keys.forEach((key) => {
      if (key.startsWith(DISTRICT_PREFIX)) {
        const district = key.slice(DISTRICT_PREFIX.length);
        index.counties.forEach((c, countyKeyValue) => {
          if (c.district === district) chosen.add(countyKeyValue);
        });
      } else {
        chosen.add(key);
      }
    });
    onChange([...chosen].flatMap((key) => index.counties.get(key)?.ids ?? []));
  }

  return (
    <MultiSearchSelect
      label={label}
      hint={hint}
      error={error}
      options={index.options}
      value={selectedCountyKeys(index, value)}
      onChange={handleChange}
      placeholder="Pesquisar concelho ou distrito..."
      chipLabel={(o) => `${o.label} · ${o.description}`}
    />
  );
}

/** Read-only list of the chosen counties. */
export function ZoneSummary({ zoneIds }) {
  const { data: zones, failed } = useCatalog(getZones);
  const index = useCountyIndex(zones);

  if (!index) return <CatalogMessage failed={failed} what="as zonas" />;

  const keys = selectedCountyKeys(index, zoneIds);
  if (keys.length === 0) {
    return (
      <p className="text-sm text-muted">
        Nenhuma zona escolhida: vê pedidos de todo o país.
      </p>
    );
  }

  return (
    <ul className="flex flex-wrap gap-2">
      {keys.map((key) => {
        const c = index.counties.get(key);
        return (
          <li key={key} className={chipClass}>
            {c.county} · {c.district}
          </li>
        );
      })}
    </ul>
  );
}

// Specialties the provider writes themselves: a category plus a free-text name.
function CustomSpecialtyEditor({ categories, value, onChange, error }) {
  const [open, setOpen] = useState(false);
  const [categoryId, setCategoryId] = useState("");
  const [name, setName] = useState("");
  const [localError, setLocalError] = useState("");

  const categoryOptions = useMemo(
    () => categories.map((c) => ({ value: c.id, label: c.label })),
    [categories],
  );
  const categoryName = (id) => categories.find((c) => c.id === id)?.label ?? "";

  function add() {
    const trimmed = name.trim();

    if (!categoryId) {
      setLocalError("Escolha uma categoria.");
      return;
    }
    if (trimmed.length < 2) {
      setLocalError("Indique o nome da especialidade.");
      return;
    }
    // the API rejects the same name twice in the same category
    const duplicate = value.some(
      (c) =>
        c.problem_category_id === categoryId &&
        c.name.toLowerCase() === trimmed.toLowerCase(),
    );
    if (duplicate) {
      setLocalError("Já adicionou esta especialidade.");
      return;
    }

    onChange([...value, { problem_category_id: categoryId, name: trimmed }]);
    setName("");
    setLocalError("");
  }

  function handleKeyDown(e) {
    // Enter adds the specialty instead of submitting the whole form
    if (e.key === "Enter") {
      e.preventDefault();
      add();
    }
  }

  const shownError = localError || error;

  return (
    <div className="flex flex-col gap-3">
      {value.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {value.map((c, index) => (
            <span key={`${c.problem_category_id}|${c.name}`} className={chipClass}>
              {c.name} · {categoryName(c.problem_category_id)}
              <button
                type="button"
                onClick={() => onChange(value.filter((_, i) => i !== index))}
                aria-label={`Remover ${c.name}`}
                className="rounded-full p-0.5 transition hover:bg-navy/20"
              >
                <X size={12} aria-hidden="true" />
              </button>
            </span>
          ))}
        </div>
      )}

      {open ? (
        <div className="flex flex-col gap-3 rounded-xl border border-dashed border-border p-4">
          <p className="text-sm font-medium text-text">Nova especialidade</p>
          <SearchSelect
            label="Categoria"
            options={categoryOptions}
            value={categoryId}
            onChange={setCategoryId}
            placeholder="Escolher categoria..."
          />
          <TextInput
            label="Nome"
            value={name}
            onChange={setName}
            onKeyDown={handleKeyDown}
            maxLength={255}
            placeholder="Ex.: Limpeza de caldeiras"
            hint="Só aparece no seu perfil e conta para a categoria escolhida."
          />
          {shownError && (
            <p role="alert" className="text-xs text-red">
              {shownError}
            </p>
          )}
          <div className="flex gap-3">
            <button
              type="button"
              onClick={add}
              className="inline-flex items-center gap-1 rounded-lg bg-navy px-3 py-1.5 text-sm font-medium text-white transition hover:bg-navy-dark"
            >
              <Plus size={14} aria-hidden="true" />
              Adicionar
            </button>
            <button
              type="button"
              onClick={() => {
                setOpen(false);
                setLocalError("");
              }}
              className="text-sm text-muted underline"
            >
              Fechar
            </button>
          </div>
        </div>
      ) : (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="inline-flex items-center gap-1 self-start text-sm font-medium text-navy hover:underline"
        >
          <Plus size={14} aria-hidden="true" />
          Não encontra a sua especialidade? Adicionar outra
        </button>
      )}
    </div>
  );
}

/**
 * Catalog specialties plus custom ones.
 * value = { service_specialty_ids, custom_specialties }; onChange receives the same shape.
 */
export function SpecialtyCoverageField({ value, onChange, errors = {} }) {
  const { data: categories, failed } = useCatalog(getCategories);

  const options = useMemo(
    () =>
      (categories ?? []).flatMap((c) =>
        c.specialties.map((s) => ({ value: s.id, label: s.name, description: c.label })),
      ),
    [categories],
  );

  if (!categories) return <CatalogMessage failed={failed} what="as especialidades" />;

  return (
    <div className="flex flex-col gap-3">
      <MultiSearchSelect
        label="Especialidades"
        hint="Se não escolher nenhuma, vê pedidos de todas as categorias."
        error={errors.service_specialty_ids}
        options={options}
        value={value.service_specialty_ids}
        onChange={(ids) => onChange({ ...value, service_specialty_ids: ids })}
        placeholder="Pesquisar especialidade..."
      />
      <CustomSpecialtyEditor
        categories={categories}
        value={value.custom_specialties ?? []}
        onChange={(list) => onChange({ ...value, custom_specialties: list })}
        error={errors.custom_specialties}
      />
    </div>
  );
}

/** Read-only list of the chosen specialties (catalog and custom). */
export function SpecialtySummary({ value }) {
  const { data: categories, failed } = useCatalog(getCategories);

  if (!categories) return <CatalogMessage failed={failed} what="as especialidades" />;

  const catalogNames = new Map(
    categories.flatMap((c) => c.specialties.map((s) => [s.id, s.name])),
  );
  const names = [
    ...value.service_specialty_ids.map((id) => catalogNames.get(id)).filter(Boolean),
    ...(value.custom_specialties ?? []).map((c) => c.name),
  ];

  if (names.length === 0) {
    return (
      <p className="text-sm text-muted">
        Nenhuma especialidade escolhida: vê pedidos de todas as categorias.
      </p>
    );
  }

  return (
    <ul className="flex flex-wrap gap-2">
      {names.map((name) => (
        <li key={name} className={chipClass}>
          {name}
        </li>
      ))}
    </ul>
  );
}

/** Zones and specialties together, for the register and add-profile forms. */
export function ProviderCoverageFields({ value, onChange, errors = {} }) {
  return (
    <div className="flex flex-col gap-5">
      <ZoneCoverageField
        value={value.zone_ids}
        onChange={(zone_ids) => onChange({ ...value, zone_ids })}
        error={errors.zone_ids}
      />
      <SpecialtyCoverageField
        value={value}
        onChange={(next) => onChange({ ...value, ...next })}
        errors={errors}
      />
    </div>
  );
}