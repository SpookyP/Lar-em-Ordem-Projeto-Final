import { useState, useEffect } from "react";
import { HousesContext } from "./housesContextObject";
import { useAuth } from "../hooks/useAuth";
import { getPropertiesPage } from "../services/api/propertyService";
import { ROLE_INFO } from "../data/roleInfo";

export function HousesProvider({ children }) {
  const { user } = useAuth();
  const [count, setCount] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    setLoading(true);

    //se a sessao ainda esta a validar (sem user): continua a carregar para o RequireHouse n redirecionar com dados antigos
    if (!user) {
      return () => {
        active = false;
      };
    }

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
        if (active) setLoading(false);
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
