import axiosClient from "./axiosClient";
import { cleanParams, toPage } from "./pagination";

function toPartner(p) {
  if (!p) return null;
  return {
    id: p.id,
    companyName: p.company_name,
    phone: p.phone,
    website: p.website,
    description: p.description,
  };
}

export function toOffer(o) {
  return {
    id: o.id,
    title: o.title,
    description: o.description,
    category: o.category ? { id: o.category.id, name: o.category.name } : null,
    url: o.url,
    startDate: o.start_date,
    endDate: o.end_date,
    active: o.active,
    partner: o.partner ? toPartner(o.partner) : null,
  };
}

export async function getOfferCategories() {
  const response = await axiosClient.get("/api/v1/offer-categories");
  return response.data.data;
}

export async function getMyOffers({ page = 1, perPage = 12, status } = {}) {
  const response = await axiosClient.get("/api/v1/offers", {
    params: cleanParams({
      page,
      per_page: perPage,
      status: status || undefined,
    }),
  });
  return toPage(response, toOffer);
}

export async function getOffer(id) {
  const response = await axiosClient.get(`/api/v1/offers/${id}`);
  return toOffer(response.data.data);
}

export async function createOffer(data) {
  const response = await axiosClient.post("/api/v1/offers", data);
  return toOffer(response.data.data);
}

export async function updateOffer(id, data) {
  const response = await axiosClient.patch(`/api/v1/offers/${id}`, data);
  return toOffer(response.data.data);
}

export async function deleteOffer(id) {
  await axiosClient.delete(`/api/v1/offers/${id}`);
}

export async function setOfferActive(id, active) {
  return updateOffer(id, { active });
}

export async function getRecommendations({
  page = 1,
  perPage = 12,
  search,
  categoryId,
} = {}) {
  const response = await axiosClient.get("/api/v1/offers/recommendations", {
    params: cleanParams({
      page,
      per_page: perPage,
      search: search || undefined,
      category_id: categoryId || undefined,
    }),
  });
  return {
    ...toPage(response, toOffer),
    categories: response.data.categories ?? [],
  };
}
