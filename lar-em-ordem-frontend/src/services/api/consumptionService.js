import axiosClient from "./axiosClient";

// Consumption types (electricity, water, gas).
export async function getConsumptionTypes() {
  const res = await axiosClient.get("/api/v1/consumption-types");
  return res.data?.data ?? res.data;
}
