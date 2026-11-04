import axiosClient from "./axiosClient";
import { toOffer } from "./offerService";

function toPartner(p) {
  return {
    id: p.id,
    companyName: p.company_name,
    phone: p.phone,
    website: p.website,
    description: p.description,
    offers: p.offers ? p.offers.map(toOffer) : [],
  };
}

export async function getPartners() {
  const response = await axiosClient.get("/api/v1/partners");
  return response.data.data.map(toPartner);
}

export async function getPartner(id) {
  const response = await axiosClient.get(`/api/v1/partners/${id}`);
  return toPartner(response.data.data);
}
