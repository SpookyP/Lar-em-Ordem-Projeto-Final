import { useState, useEffect } from "react";
import { useAuth } from "../../hooks/useAuth";
import { getProperty } from "../../services/api/propertyService";
import { DataRow } from "./DataRow";

function formatDate(value) {
  if (!value) return null;
  const d = new Date(value);
  return isNaN(d) ? value : d.toLocaleDateString("pt-PT");
}

export function HouseDetails() {
  const { activeHouse } = useAuth();
  const [house, setHouse] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    if (!activeHouse) {
      setLoading(false);
      return;
    }
    setLoading(true);
    getProperty(activeHouse.id)
      .then((data) => {
        if (active) setHouse(data);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar a habitação.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [activeHouse]);

  if (!activeHouse)
    return <p className="text-sm text-muted">Nenhuma habitação selecionada.</p>;
  if (loading) return <p className="text-sm text-muted">A carregar...</p>;
  if (error) return <p className="text-sm text-red-600">{error}</p>;
  if (!house) return null;

  const a = house.address;
  const addressLine = [a.street, a.door, a.fraction].filter(Boolean).join(", ");

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">A minha habitação</h2>
      <dl className="flex flex-col gap-3 text-sm">
        <DataRow label="Morada" value={addressLine} />
        <DataRow label="Código postal" value={a.postalCode} />
        <DataRow label="Localidade" value={a.location} />
        <DataRow label="Concelho" value={a.county} />
        <DataRow label="Distrito" value={a.district} />
        <DataRow label="Tipo" value={house.typeLabel} />
        <DataRow label="Tipologia" value={house.typologyLabel} />
        <DataRow label="Vínculo" value={house.bondLabel} />
        <DataRow label="Área" value={house.area ? `${house.area} m²` : null} />
        <DataRow label="Data de início" value={formatDate(house.startDate)} />
        <DataRow label="Data de fim" value={formatDate(house.endDate)} />
      </dl>
    </div>
  );
}
