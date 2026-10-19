export const PASSWORD_RULES = [
  {
    id: "length",
    label: "Pelo menos 12 caracteres",
    test: (pw) => pw.length >= 12,
  },
  {
    id: "lowercase",
    label: "Pelo menos uma letra minúscula",
    test: (pw) => /[a-z]/.test(pw),
  },
  {
    id: "uppercase",
    label: "Pelo menos uma letra maiúscula",
    test: (pw) => /[A-Z]/.test(pw),
  },
  {
    id: "number",
    label: "Pelo menos um número",
    test: (pw) => /[0-9]/.test(pw),
  },
  {
    id: "symbol",
    label: "Pelo menos um símbolo (! ? @ # ...)",
    test: (pw) => /[^A-Za-z0-9]/.test(pw),
  },
];

export function checkPassword(pw) {
  return PASSWORD_RULES.map((rule) => ({
    id: rule.id,
    label: rule.label,
    ok: rule.test(pw),
  }));
}

export function isPasswordValid(pw) {
  return PASSWORD_RULES.every((rule) => rule.test(pw));
}
