export const EMPTY_COVERAGE = {
  zone_ids: [],
  service_specialty_ids: [],
  custom_specialties: [],
};

/** The three coverage keys the API expects, ready to merge into a request payload. */
export function coverageToPayload(coverage) {
  return {
    zone_ids: coverage.zone_ids,
    service_specialty_ids: coverage.service_specialty_ids,
    custom_specialties: coverage.custom_specialties ?? [],
  };
}
