import axiosClient from "./axiosClient";
import { slugify } from "../../data/assistanceInfo";
import { cleanParams, toPage } from "./pagination";

function toRequestSummary(a) {
  const name = a.category?.name ?? "";
  return {
    id: a.id,
    title: a.title,
    status: a.status,
    category: { slug: slugify(name), name },
    address: a.property ?? {},
  };
}

export function toProposal(p) {
  return {
    id: p.id,
    value: p.estimated_value,
    description: p.description,
    validity: p.validity,
    availableDate: p.available_date,
    availableFrom: p.available_from,
    availableUntil: p.available_until,
    status: p.status,
    createdAt: p.created_at,
    provider: p.service_provider
      ? {
          companyName: p.service_provider.company_name,
          phone: p.service_provider.phone,
          email: p.service_provider.provider_email,
        }
      : null,
    request: p.assistance_request ? toRequestSummary(p.assistance_request) : null,
  };
}

function toPayload(data) {
  return {
    estimated_value: Number(data.value),
    description: data.description,
    validity: data.validity,
    available_date: data.availableDate,
    available_from: data.availableFrom,
    available_until: data.availableUntil,
  };
}

export async function getMyProposals({ page, perPage, status }) {
  const response = await axiosClient.get("/api/v1/proposals/mine", {
    params: cleanParams({ page, per_page: perPage, status }),
  });
  return toPage(response, toProposal);
}

export async function createProposal(requestId, data) {
  const response = await axiosClient.post(
    `/api/v1/assistance-requests/${requestId}/proposals`,
    toPayload(data),
  );
  return toProposal(response.data.data);
}

export async function updateProposal(id, data) {
  const response = await axiosClient.patch(
    `/api/v1/proposals/${id}`,
    toPayload(data),
  );
  return toProposal(response.data.data);
}

export async function withdrawProposal(id) {
  const response = await axiosClient.patch(`/api/v1/proposals/${id}/withdraw`);
  return toProposal(response.data.data);
}

export async function acceptProposal(id) {
  const response = await axiosClient.patch(`/api/v1/proposals/${id}/accept`);
  return toProposal(response.data.data);
}

export async function rejectProposal(id) {
  const response = await axiosClient.patch(`/api/v1/proposals/${id}/reject`);
  return toProposal(response.data.data);
}