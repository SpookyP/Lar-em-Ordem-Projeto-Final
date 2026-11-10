const euro = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

export function formatMoney(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? euro.format(amount) : "";
}

//a rua e a fracao so chegam da API ao dono do pedido e ao prestador aceite
export function formatAddress(address) {
  return [address.street, address.fraction, address.location, address.county]
    .filter(Boolean)
    .join(", ");
}

// a building's address on one line: "Rua Brito Capelo, Nº 100, 4450-073, Matosinhos"
export function addressLine(address) {
  if (!address) return "";
  return [address.street, address.door, address.postal_code, address.county]
    .filter(Boolean)
    .join(", ");
}

// keeps an optional leading "+" and up to 14 digits (the API allows 15 characters)
export function cleanPhone(text) {
  const raw = String(text ?? "").trim();
  const digits = raw.replace(/\D/g, "").slice(0, 14);
  return raw.startsWith("+") ? `+${digits}` : digits;
}

// "912345678" -> "912 345 678", "+351912345678" -> "+351 912 345 678"
export function formatPhone(value) {
  const clean = cleanPhone(value);
  const match = clean.match(/^(\+351)?(\d{3})(\d{3})(\d{3})$/);
  if (!match) return clean;
  const [, prefix, a, b, c] = match;
  return [prefix, a, b, c].filter(Boolean).join(" ");
}