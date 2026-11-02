import axiosClient from "./axiosClient";

function toOffer(o) {
  return {
    id: o.id,
    title: o.title,
    description: o.description,
    type: o.type,
    url: o.url,
    startDate: o.start_date,
    endDate: o.end_date,
    active: o.active,
  };
}

export async function getMyOffers(filter = "all") {
  const params = {};
  if (filter === "active") params.active = true;
  if (filter === "inactive") params.active = false;

  const response = await axiosClient.get("/api/v1/offers", { params });
  return response.data.data.map(toOffer);
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
