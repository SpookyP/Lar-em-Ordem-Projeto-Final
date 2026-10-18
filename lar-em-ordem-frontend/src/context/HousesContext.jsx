import { useState, useEffect } from "react";
import { HousesContext } from "./housesContextObject";
import { useAuth } from "../hooks/useAuth";
import { getAllProperties } from "../services/api/propertyService";
import { ROLE_INFO } from "../data/roleInfo";

export function HousesProvider({ children }) {
  const { user } = useAuth();
  const [houses, setHouses] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;

    const hasHouses = (user?.roles ?? []).some((r) => ROLE_INFO[r]?.hasHouses);

    if (!user || !hasHouses) {
      setHouses([]);
      return;
    }

    setLoading(true);
    getAllProperties()
      .then((lista) => {
        if (active) setHouses(lista);
      })
      .catch(() => {
        if (active) setHouses([]);
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [user]);

  async function refreshHouses() {
    setLoading(true);
    try {
      const lista = await getAllProperties();
      setHouses(lista);
    } finally {
      setLoading(false);
    }
  }

  const value = { houses, loading, refreshHouses };

  return (
    <HousesContext.Provider value={value}>{children}</HousesContext.Provider>
  );
}
