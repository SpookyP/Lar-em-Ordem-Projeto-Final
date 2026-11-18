import axiosClient from "./axiosClient";

// Tipos de consumo (eletricidade, água, gás).
export async function getConsumptionTypes() {
  const res = await axiosClient.get("/api/v1/consumption-types");
  return res.data?.data ?? res.data;
}
