import axiosClient from "./axiosClient";

let zonesPromise = null;

export function getZones() {
  if (!zonesPromise) {
    zonesPromise = axiosClient
      .get("/api/v1/service-zones")
      .then((response) =>
        response.data.data.map((z) => ({
          id: z.id,
          district: z.district,
          county: z.county,
          location: z.location,
        })),
      )
      .catch((error) => {
        zonesPromise = null;
        throw error;
      });
  }
  return zonesPromise;
}

export async function getMyProviderCoverage() {
  const response = await axiosClient.get("/api/v1/service-providers/me");
  const provider = response.data.data;
  const specialties = provider.specialties ?? [];

  return {
    zone_ids: (provider.zones ?? []).map((z) => z.id),
    service_specialty_ids: specialties
      .filter((s) => !s.is_custom)
      .map((s) => s.specialty?.id)
      .filter((id) => id != null),
    custom_specialties: specialties
      .filter((s) => s.is_custom && s.category?.id != null)
      .map((s) => ({ problem_category_id: s.category.id, name: s.display_name })),
  };
}

export function clearCatalogCache() {
  zonesPromise = null;
}