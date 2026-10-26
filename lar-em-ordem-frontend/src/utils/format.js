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