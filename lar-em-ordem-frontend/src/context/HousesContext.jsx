import { useState, useEffect } from "react";
import { HousesContext } from "./housesContextObject";
import { useAuth } from "../hooks/useAuth";
import { getProperties } from "../services/api/propertyService";
import { ROLE_INFO } from "../data/roleInfo";

export function HousesProvider({ children }) {
  const { user } = useAuth();
  const [count, setCount] = useState(0);

  useEffect(() => {
    let active = true;
    const hasHouses = (user?.roles ?? []).some((r) => ROLE_INFO[r]?.hasHouses);

    if (!user || !hasHouses) {
      setCount(0);
      return;
    }

    getProperties(1)
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
    const data = await getProperties(1);
    setCount(data.total);
  }

  const value = { count, refreshHouses };

  return (
    <HousesContext.Provider value={value}>{children}</HousesContext.Provider>
  );
}
