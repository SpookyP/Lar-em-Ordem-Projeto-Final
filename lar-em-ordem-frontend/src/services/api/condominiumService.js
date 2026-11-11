import axiosClient from "./axiosClient";
import { cleanParams, toPage } from "./pagination";

export function toNotice(n) {
  return {
    id: n.id,
    title: n.title,
    message: n.message,
    priority: n.priority,
    publishedAt: n.published_at,
    expiresAt: n.expires_at,
    read: n.read ?? null,
    readersCount: n.readers_count ?? null,
  };
}

// API (snake_case) -> app (camelCase); fields the API leaves out stay null or empty
export function toCondominium(c) {
  return {
    id: c.id,
    name: c.name,
    reserveFund: c.reserve_fund,
    canManage: c.can_manage ?? false,
    inviteCode: c.invite_code ?? null,
    propertiesCount: c.properties_count ?? 0,
    transferExpiresAt: c.transfer_expires_at ?? null,
    address: c.address ?? null,
    admin: c.admin ?? null,
    properties: c.properties ?? [],
    notices: (c.notices ?? []).map(toNotice),
    createdAt: c.created_at,
  };
}

// condominiums managed by the signed-in administrator
export async function getMyCondominiums({ page = 1, perPage = 6 } = {}) {
  const response = await axiosClient.get("/api/v1/condominiums", {
    params: cleanParams({ page, per_page: perPage }),
  });
  return toPage(response, toCondominium);
}

export async function getCondominium(id) {
  const response = await axiosClient.get(`/api/v1/condominiums/${id}`);
  return toCondominium(response.data.data);
}

export async function createCondominium(data) {
  const response = await axiosClient.post("/api/v1/condominiums", data);
  return toCondominium(response.data.data);
}

export async function updateCondominium(id, data) {
  const response = await axiosClient.patch(`/api/v1/condominiums/${id}`, data);
  return toCondominium(response.data.data);
}

export async function deleteCondominium(id) {
  await axiosClient.delete(`/api/v1/condominiums/${id}`);
}

// the old code stops working; homes that already joined stay
export async function regenerateInviteCode(id) {
  return updateCondominium(id, { regenerate_invite_code: true });
}

// unlinks a home from the condominium (the administrator can remove any home of the building)
export async function removeProperty(condominiumId, propertyId) {
  await axiosClient.patch(`/api/v1/condominiums/${condominiumId}/leave`, {
    property_id: propertyId,
  });
}

// every notice of the condominium, expired ones included, newest first
export async function getNotices({ condominiumId, page = 1, perPage = 5 }) {
  const response = await axiosClient.get(`/api/v1/condominiums/${condominiumId}/notices`, {
    params: cleanParams({ page, per_page: perPage }),
  });
  return toPage(response, toNotice);
}

// publishing emails the residents and pushes the notice to their homes in real time
export async function createNotice(condominiumId, data) {
  const response = await axiosClient.post(`/api/v1/condominiums/${condominiumId}/notices`, data);
  return toNotice(response.data.data);
}

// a changed notice that is still current becomes unread again and is sent again
export async function updateNotice(noticeId, data) {
  const response = await axiosClient.patch(`/api/v1/condominium-notices/${noticeId}`, data);
  return toNotice(response.data.data);
}

export async function deleteNotice(noticeId) {
  await axiosClient.delete(`/api/v1/condominium-notices/${noticeId}`);
}

export function toDocument(d) {
  return {
    id: d.id,
    title: d.title,
    description: d.description ?? "",
    documentDate: d.document_date,
    category: d.category ?? null,
    file: d.file
      ? { name: d.file.name, mimeType: d.file.mime_type, size: d.file.size }
      : null,
    createdAt: d.created_at,
  };
}

// a page of documents plus the categories, which every listing brings for the filter
function toDocumentsPage(response) {
  return { ...toPage(response, toDocument), extra: { categories: response.data.categories ?? [] } };
}

export async function getCondominiumDocuments({ condominiumId, categoryId, page = 1, perPage = 5 }) {
  const response = await axiosClient.get(`/api/v1/condominiums/${condominiumId}/documents`, {
    params: cleanParams({ page, per_page: perPage, category_id: categoryId }),
  });
  return toDocumentsPage(response);
}

// data: { file, title, description, document_date, condominium_document_category_id }
export async function uploadDocument(condominiumId, data) {
  const body = new FormData();
  Object.entries(data).forEach(([key, value]) => {
    if (value != null && value !== "") body.append(key, value);
  });
  const response = await axiosClient.post(`/api/v1/condominiums/${condominiumId}/documents`, body);
  return toDocument(response.data.data);
}

// only the details change; the file stays the same
export async function updateDocument(documentId, data) {
  const response = await axiosClient.patch(`/api/v1/condominium-documents/${documentId}`, data);
  return toDocument(response.data.data);
}

export async function deleteDocument(documentId) {
  await axiosClient.delete(`/api/v1/condominium-documents/${documentId}`);
}

// temporary link (30 minutes) to view or download the file
export async function getDocumentLink(documentId) {
  const response = await axiosClient.get(`/api/v1/condominium-documents/${documentId}/download`);
  return response.data.data.url;
}

// the code is only ever shown in this answer; a new one replaces the previous
export async function startTransfer(condominiumId) {
  const response = await axiosClient.post(`/api/v1/condominiums/${condominiumId}/transfer`);
  return {
    code: response.data.data.transfer_code,
    expiresAt: response.data.data.transfer_expires_at,
  };
}

export async function cancelTransfer(condominiumId) {
  await axiosClient.delete(`/api/v1/condominiums/${condominiumId}/transfer`);
}

// the new administrator accepts the condominium with the code they were given
export async function claimCondominium(transferCode) {
  const response = await axiosClient.post("/api/v1/condominiums/claim", {
    transfer_code: transferCode,
  });
  return toCondominium(response.data.data);
}