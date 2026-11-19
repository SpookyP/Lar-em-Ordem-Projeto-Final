import { useState, useEffect } from "react";
import { HousesContext } from "./housesContextObject";
import { useAuth } from "../hooks/useAuth";
import { getPropertiesPage } from "../services/api/propertyService";
import { ROLE_INFO } from "../data/roleInfo";

export function HousesProvider({ children }) {
  const { user } = useAuth();
  const [count, setCount] = useState(0);
  const [loadedFor, setLoadedFor] = useState(null);

  const loading = !user || loadedFor !== user;

  useEffect(() => {
    if (!user) return;
    let active = true;

    const hasHouses = (user?.roles ?? []).some((r) => ROLE_INFO[r]?.hasHouses);

    const request = hasHouses
      ? getPropertiesPage({ page: 1 })
      : Promise.resolve({ total: 0 });

    request
      .then((data) => {
        if (active) setCount(data.total);
      })
      .catch(() => {
        if (active) setCount(0);
      })
      .finally(() => {
        if (active) setLoadedFor(user);
      });

    return () => {
      active = false;
    };
  }, [user]);

  async function refreshHouses() {
    const data = await getPropertiesPage({ page: 1 });
    setCount(data.total);
  }

  const value = { count, loading, refreshHouses };

  return (
    <HousesContext.Provider value={value}>{children}</HousesContext.Provider>
  );
}
