import { useContext } from "react";
import { HousesContext } from "../context/housesContextObject";

export function useHouses() {
  const context = useContext(HousesContext);

  if (!context) {
    throw new Error("useHouses tem de ser usado dentro de um <HousesProvider>");
  }

  return context;
}
