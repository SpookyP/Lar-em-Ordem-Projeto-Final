import { useState, useEffect } from "react";
import { HousesContext } from "./housesContextObject";
import { useAuth } from "../hooks/useAuth";
import { getPropertiesPage } from "../services/api/propertyService";
import { ROLE_INFO } from "../data/roleInfo";

export function HousesProvider({ children }) {
  const { user } = useAuth();
  const [count, setCount] = useState(0);

  useEffect(() => {
    let active = true;
    const hasHouses = (user?.roles ?? []).some((r) => ROLE_INFO[r]?.hasHouses);

    const request =
      !user || !hasHouses
        ? Promise.resolve({ total: 0 })
        : getPropertiesPage({ page: 1 });

    request
      .then((data) => {
        if (active) setCount(data.total);
      })
      .catch(() => {
        if (active) setCount(0);
      });

    return () => {
      active = false;
    };
  }, [user]);

  async function refreshHouses() {
    const data = await getPropertiesPage({ page: 1 });
    setCount(data.total);
  }

  const value = { count, refreshHouses };

  return (
    <HousesContext.Provider value={value}>{children}</HousesContext.Provider>
  );
}
