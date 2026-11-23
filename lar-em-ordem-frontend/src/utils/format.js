const euro = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

// shows an explicit "+" on positive values, so a difference reads as a gain or a saving
const euroSigned = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
  signDisplay: "exceptZero",
});

// compact notation for chart axis labels, e.g. 1500 -> "1,5 mil" (no currency symbol)
const compactNumber = new Intl.NumberFormat("pt-PT", {
  notation: "compact",
  maximumFractionDigits: 1,
});

export function formatMoney(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? euro.format(amount) : "";
}

export function formatMoneySigned(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? euroSigned.format(amount) : "";
}

export function formatCompact(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? compactNumber.format(amount) : "";
}

export function formatNumber(value, { decimals = 2, signed = false } = {}) {
  const amount = Number(value);
  if (!Number.isFinite(amount)) return "";
  return amount.toLocaleString("pt-PT", {
    maximumFractionDigits: decimals,
    signDisplay: signed ? "exceptZero" : "auto",
  });
}

export function formatAddress(address) {
  const parts = [
    address.street,
    address.postal_code,
    address.location,
    address.county,
  ].filter(Boolean);
  return [...new Set(parts)].join(", ") || "Morada removida";
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

// "245 KB", "1,2 MB"
export function formatBytes(bytes) {
  const size = Number(bytes);
  if (!Number.isFinite(size) || size <= 0) return "";
  if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
  return `${(size / (1024 * 1024)).toLocaleString("pt-PT", { maximumFractionDigits: 1 })} MB`;
}
