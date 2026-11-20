import { useAuth } from "../../hooks/useAuth";
import { getProperty } from "../../services/api/propertyService";
import {
  House,
  MapPin,
  Map,
  BedDouble,
  FileSignature,
  Ruler,
  CalendarDays,
  CalendarClock,
} from "lucide-react";
import { useFetch } from "../../hooks/useFetch";

function formatDate(value) {
  if (!value) return null;
  const d = new Date(value);
  return isNaN(d) ? value : d.toLocaleDateString("pt-PT");
}

function Tile({ icon: Icon, label, value }) {
  return (
    <div className="flex items-start gap-3 rounded-xl bg-surface p-3.5">
      <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-card text-navy shadow-sm">
        <Icon size={16} />
      </span>
      <div className="min-w-0">
        <dt className="text-[11px] font-medium uppercase tracking-wide text-muted">
          {label}
        </dt>
        <dd className="mt-0.5 wrap-break-word font-semibold text-navy">
          {value || "—"}
        </dd>
      </div>
    </div>
  );
}

export function HouseDetails() {
  const { activeHouse } = useAuth();
  const {
    data: house,
    loading,
    error,
  } = useFetch(() => getProperty(activeHouse.id), [activeHouse?.id], {
    enabled: Boolean(activeHouse),
    errorMessage: "Não foi possível carregar a habitação.",
  });

  if (!activeHouse)
    return <p className="text-sm text-muted">Nenhuma habitação selecionada.</p>;
  if (loading) return <p className="text-sm text-muted">A carregar...</p>;
  if (error) return <p className="text-sm text-red-600">{error}</p>;
  if (!house) return null;

  const a = house.address;
  const addressLine = [a.street, a.door, a.fraction].filter(Boolean).join(", ");
  const locationLine = [a.postalCode, a.location].filter(Boolean).join(" ");

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">A minha habitação</h2>
      <div className="flex items-start gap-3 rounded-2xl bg-surface p-4">
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-navy text-white">
          <House size={20} />
        </span>
        <div className="min-w-0">
          <p className="font-medium text-text">{addressLine || "—"}</p>
          {locationLine && (
            <p className="mt-0.5 flex items-center gap-1 text-sm text-muted">
              <MapPin size={14} /> {locationLine}
            </p>
          )}
        </div>
      </div>

      <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <Tile icon={House} label="Tipo" value={house.typeLabel} />
        <Tile icon={BedDouble} label="Tipologia" value={house.typologyLabel} />
        <Tile icon={FileSignature} label="Vínculo" value={house.bondLabel} />
        <Tile
          icon={Ruler}
          label="Área"
          value={house.area ? `${house.area} m²` : null}
        />
        <Tile icon={MapPin} label="Concelho" value={a.county} />
        <Tile icon={Map} label="Distrito" value={a.district} />
        <Tile
          icon={CalendarDays}
          label="Data de início"
          value={formatDate(house.startDate)}
        />
        {house.endDate && (
          <Tile
            icon={CalendarClock}
            label="Data de fim"
            value={formatDate(house.endDate)}
          />
        )}
      </dl>
    </div>
  );
}
