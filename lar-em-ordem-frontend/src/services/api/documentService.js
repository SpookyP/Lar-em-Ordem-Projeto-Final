import axiosClient from "./axiosClient";
import { cleanParams, toPage } from "./pagination";

//Estado calculado a partir da data de validade
export function getDocumentStatus(expirationDate) {
  if (!expirationDate) return "valid";
  const exp = new Date(expirationDate);
  if (Number.isNaN(exp.getTime())) return "valid";

  const MS_PER_DAY = 1000 * 60 * 60 * 24;
  const today = new Date();
  const startToday = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate(),
  );
  const startExp = new Date(exp.getFullYear(), exp.getMonth(), exp.getDate());
  const days = Math.round((startExp - startToday) / MS_PER_DAY);

  if (days < 0) return "expired";
  if (days <= 30) return "expiring";
  return "valid";
}

function toDocument(d) {
  return {
    id: d.id,
    propertyId: d.property_id,
    categoryId: d.document_category_id,
    category: d.category ?? null,
    name: d.name,
    description: d.description ?? "",
    fileId: d.file_id ?? null,
    hasFile: Boolean(d.has_file),
    issueDate: d.issue_date ?? null,
    expirationDate: d.expiration_date ?? null,
    status: getDocumentStatus(d.expiration_date ?? null),
    createdAt: d.created_at,
    updatedAt: d.updated_at,
  };
}

export async function getPropertyDocuments({
  propertyId,
  page = 1,
  perPage = 50,
}) {
  const response = await axiosClient.get(
    `/api/v1/properties/${propertyId}/documents`,
    {
      params: cleanParams({ page, per_page: perPage }),
    },
  );
  return toPage(response, toDocument);
}

export async function getDocument(id) {
  const response = await axiosClient.get(`/api/v1/documents/${id}`);
  return toDocument(response.data.data);
}

export async function uploadDocument(
  propertyId,
  { name, documentCategoryId, description, file, expirationDate, issueDate },
) {
  const formData = new FormData();
  formData.append("property_id", propertyId);
  formData.append("name", name);
  formData.append("document_category_id", documentCategoryId);
  if (description) formData.append("description", description);
  if (file) formData.append("file", file);
  if (expirationDate) formData.append("expiration_date", expirationDate);
  if (issueDate) formData.append("issue_date", issueDate);

  const response = await axiosClient.post(
    `/api/v1/properties/${propertyId}/documents`,
    formData,
  );
  return toDocument(response.data.data);
}

export async function updateDocument(
  id,
  { name, documentCategoryId, description, expirationDate, issueDate, file },
) {
  const formData = new FormData();
  formData.append("_method", "PUT");
  if (name !== undefined) formData.append("name", name);
  if (documentCategoryId !== undefined)
    formData.append("document_category_id", documentCategoryId);
  if (description !== undefined) formData.append("description", description);
  if (expirationDate !== undefined)
    formData.append("expiration_date", expirationDate);
  if (issueDate !== undefined) formData.append("issue_date", issueDate);
  if (file) formData.append("file", file);

  const response = await axiosClient.post(`/api/v1/documents/${id}`, formData);
  return toDocument(response.data.data);
}

export async function getDocumentDownloadUrl(id) {
  const response = await axiosClient.get(`/api/v1/documents/${id}/download`);
  return response.data?.url ?? response.data?.data?.url ?? response.data;
}

export async function deleteDocument(id) {
  await axiosClient.delete(`/api/v1/documents/${id}`);
}

export async function getDocumentCategories() {
  const response = await axiosClient.get("/api/v1/document-categories");
  return response.data.data ?? response.data;
}
