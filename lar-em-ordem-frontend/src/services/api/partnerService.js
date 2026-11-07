import axiosClient from "./axiosClient";
import { toOffer } from "./offerService";
import { cleanParams, toPage } from "./pagination";

function toPartner(p) {
  return {
    id: p.id,
    companyName: p.company_name,
    phone: p.phone,
    website: p.website,
    description: p.description,
    currentOffersCount: p.current_offers_count,
    offers: p.offers ? p.offers.map(toOffer) : [],
  };
}

export async function getPartnersPage({ page = 1, perPage = 12, search } = {}) {
  const response = await axiosClient.get("/api/v1/partners", {
    params: cleanParams({
      page,
      per_page: perPage,
      search: search || undefined,
    }),
  });
  return toPage(response, toPartner);
}

export async function getPartner(id, { page = 1, perPage = 4 } = {}) {
  const response = await axiosClient.get(`/api/v1/partners/${id}`, {
    params: cleanParams({ page, per_page: perPage }),
  });
  const partner = toPartner(response.data.data);
  const meta = response.data.offers_meta ?? {};
  return {
    ...partner,
    offersPage: meta.current_page ?? 1,
    offersTotalPages: meta.last_page ?? 1,
    offersTotal: meta.total ?? partner.offers.length,
  };
}
