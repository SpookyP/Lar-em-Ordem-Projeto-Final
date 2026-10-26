import axiosClient from "./axiosClient";
import { STATUS_LABELS, slugify } from "../../data/assistanceInfo";
import { toProposal } from "./proposalService";
import { cleanParams, toPage } from "./pagination";

function toAssistance(a) {
  const name = a.category?.name ?? "";
  return {
    id: a.id,
    title: a.title,
    description: a.description,
    category: { id: a.category?.id, slug: slugify(name), name },
    status: a.status,
    statusLabel: STATUS_LABELS[a.status] ?? a.status,
    proposalsCount: a.pending_proposals_count ?? 0,
    createdAt: a.created_at,
  };
}

function toAssistanceDetail(a) {
  return {
    ...toAssistance(a),
    closedAt: a.closed_at,
    address: a.property ?? {},
    attachments: (a.attachments ?? []).map((f) => ({ id: f.id, url: f.url })),
    proposals: (a.proposals ?? []).map(toProposal),
  };
}

export async function getCategories() {
  const response = await axiosClient.get("/api/v1/problem-categories");
  return response.data.data.map((c) => ({ id: c.id, label: c.name }));
}

export async function getAssistanceRequests({
  propertyId,
  page,
  perPage,
  status,
}) {
  const response = await axiosClient.get("/api/v1/assistance-requests", {
    params: cleanParams({
      property_id: propertyId,
      page,
      per_page: perPage,
      status,
    }),
  });
  return toPage(response, toAssistance);
}

export async function getAssistanceRequest(id) {
  const response = await axiosClient.get(`/api/v1/assistance-requests/${id}`);
  return toAssistanceDetail(response.data.data);
}

export async function createAssistanceRequest({ images = [], ...fields }) {
  const body = new FormData();
  Object.entries(fields).forEach(([key, value]) => body.append(key, value));
  images.forEach((file) => body.append("images[]", file));

  const response = await axiosClient.post("/api/v1/assistance-requests", body);
  return toAssistance(response.data.data);
}

export async function cancelAssistanceRequest(id) {
  const response = await axiosClient.patch(
    `/api/v1/assistance-requests/${id}/cancel`,
  );
  return toAssistance(response.data.data);
}