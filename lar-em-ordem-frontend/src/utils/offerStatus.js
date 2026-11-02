export function getOfferStatus(offer) {
  const today = new Date().toISOString().slice(0, 10);

  if (!offer.active) {
    return { label: "Inativa", className: "bg-surface text-muted" };
  }
  if (offer.endDate && offer.endDate < today) {
    return { label: "Expirada", className: "bg-red-light text-red" };
  }
  if (offer.startDate && offer.startDate > today) {
    return { label: "Agendada", className: "bg-orange-light text-orange" };
  }
  return { label: "Em vigor", className: "bg-teal/15 text-teal" };
}
