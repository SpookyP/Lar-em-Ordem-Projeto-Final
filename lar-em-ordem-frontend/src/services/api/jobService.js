import axiosClient from "./axiosClient";
import { slugify } from "../../data/assistanceInfo";

function toJob(a) {
  const name = a.category?.name ?? "";
  return {
    id: a.id,
    title: a.title,
    description: a.description,
    category: { id: a.category?.id, slug: slugify(name), name },
    address: a.property ?? {},
    createdAt: a.created_at,
  };
}

export async function getAvailableJobs() {
  const response = await axiosClient.get("/api/v1/assistance-requests/available");
  return response.data.data.map(toJob);
}