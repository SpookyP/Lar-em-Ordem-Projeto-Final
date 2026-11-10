import axiosClient from "./axiosClient";
import { cleanParams, toPage } from "./pagination";

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
    notices: c.notices ?? [],
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