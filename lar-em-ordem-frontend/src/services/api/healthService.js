import axiosClient from "./axiosClient";

export async function getHealth() {
  const response = await axiosClient.get("/api/v1/health");
  return response.data.services;
}