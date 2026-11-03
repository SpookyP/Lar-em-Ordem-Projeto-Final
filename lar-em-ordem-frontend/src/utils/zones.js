const collator = new Intl.Collator("pt", { sensitivity: "base" });

function sortedUnique(values) {
  return [...new Set(values)].sort(collator.compare);
}

export function getDistricts(zones) {
  return sortedUnique(zones.map((z) => z.district));
}

export function getCounties(zones, district) {
  return sortedUnique(
    zones.filter((z) => z.district === district).map((z) => z.county),
  );
}

export function getLocations(zones, district, county) {
  return sortedUnique(
    zones
      .filter((z) => z.district === district && z.county === county)
      .map((z) => z.location),
  );
}

export function zoneIdsFor(zones, district, county) {
  return zones
    .filter(
      (z) =>
        z.district === district && (county === undefined || z.county === county),
    )
    .map((z) => z.id);
}