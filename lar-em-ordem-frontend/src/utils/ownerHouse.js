export function isOwnerHouse(h) {
  if (!h) return false;
  return (h.bondLabel ?? "").toLowerCase().includes("propriet");
}
