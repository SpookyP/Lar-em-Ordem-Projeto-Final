import axiosClient from "./axiosClient";
import { STATUS_LABELS, slugify } from "../../data/assistanceInfo";

function toAssistance(a) {
  const name = a.category.name ?? "";
  return {
    id: a.id,
    title: a.title,
    description: a.description,
    category: { id: a.category?.id, slug: slugify(name), name },
    status: a.status,
    statusLabel: STATUS_LABELS[a.status] ?? a.status,
    createdAt: a.created_at,
  };
}

export async function getCategories() {
  const response = await axiosClient.get("/api/v1/problem-categories");
  return response.data.data.map((c) => ({ id: c.id, label: c.name }));
}

export async function getAssistanceRequests(status) {
  const response = await axiosClient.get("/api/v1/assistance-requests", {
    params: { status },
  });
  return response.data.data.map(toAssistance);
}

export async function createAssistanceRequest(data) {
  const response = await axiosClient.post("/api/v1/assistance-requests", data);
  return toAssistance(response.data.data);
}

export async function uploadAttachments(requestId, files) {
  const body = new FormData();
  files.forEach((f) => body.append("images[]", f));
  await axiosClient.post(
    `/api/v1/assistance-requests/${requestId}/attachments`,
    body,
  );
}
