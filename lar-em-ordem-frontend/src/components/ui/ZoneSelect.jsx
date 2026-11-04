import { useEffect, useState } from "react";
import { getZones } from "../../services/api/catalogService";
import { getCounties, getDistricts, getLocations } from "../../utils/zones";

const selectClass =
  "rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy disabled:bg-surface disabled:text-muted";

function withCurrent(options, current) {
  return current && !options.includes(current) ? [current, ...options] : options;
}

function Select({ id, label, value, options, placeholder, disabled, onChange }) {
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="text-sm text-muted">
        {label}
      </label>
      <select
        id={id}
        name={id}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        required
        className={selectClass}
      >
        <option value="" disabled>
          {placeholder}
        </option>
        {options.map((o) => (
          <option key={o} value={o}>
            {o}
          </option>
        ))}
      </select>
    </div>
  );
}

export function ZoneSelect({ district, county, location, onChange }) {
  const [zones, setZones] = useState(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let active = true;
    getZones()
      .then((data) => active && setZones(data))
      .catch(() => active && setFailed(true));
    return () => {
      active = false;
    };
  }, []);

  if (failed) {
    return (
      <p className="text-sm text-red-600">Não foi possível carregar as zonas.</p>
    );
  }
  if (!zones) return <p className="text-sm text-muted">A carregar zonas...</p>;

  const districts = withCurrent(getDistricts(zones), district);
  const counties = district
    ? withCurrent(getCounties(zones, district), county)
    : [];
  const locations =
    district && county
      ? withCurrent(getLocations(zones, district, county), location)
      : [];

  return (
    <>
      <Select
        id="district"
        label="Distrito"
        value={district}
        options={districts}
        placeholder="Escolher distrito..."
        onChange={(value) => onChange({ district: value, county: "", location: "" })}
      />
      <Select
        id="county"
        label="Concelho"
        value={county}
        options={counties}
        placeholder="Escolher concelho..."
        disabled={!district}
        onChange={(value) => onChange({ district, county: value, location: "" })}
      />
      <Select
        id="location"
        label="Localidade"
        value={location}
        options={locations}
        placeholder="Escolher localidade..."
        disabled={!county}
        onChange={(value) => onChange({ district, county, location: value })}
      />
    </>
  );
}