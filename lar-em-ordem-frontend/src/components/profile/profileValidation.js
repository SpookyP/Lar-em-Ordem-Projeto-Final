import { isPasswordValid } from "../../utils/passwordValidation";

// same rules as the API, so most mistakes show up before sending; the API still checks everything
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function isHttpUrl(text) {
  try {
    const url = new URL(text);
    return url.protocol === "http:" || url.protocol === "https:";
  } catch {
    return false;
  }
}

const rules = {
  name: (v) => (v.trim() ? null : "Indique o seu nome."),
  email: (v) => (EMAIL.test(v.trim()) ? null : "Indique um email válido."),
  password: (v) =>
    isPasswordValid(v) ? null : "A password não cumpre todos os requisitos.",
  password_confirmation: (v, all) =>
    v === all.password ? null : "As passwords não coincidem.",
  nif: (v) => (/^\d{9}$/.test(v) ? null : "O NIF tem de ter 9 dígitos."),
  company_name: (v) => (v.trim() ? null : "Indique o nome da empresa."),
  phone: (v) =>
    v.replace(/\D/g, "").length >= 9
      ? null
      : "Indique um número com pelo menos 9 dígitos.",
  provider_email: (v) =>
    !v.trim() || EMAIL.test(v.trim()) ? null : "Indique um email válido.",
  website: (v) =>
    !v.trim() || isHttpUrl(v.trim())
      ? null
      : "Indique um endereço começado por http:// ou https://.",
  description: (v) => (v.trim() ? null : "Escreva uma breve descrição."),
  admin_type: (v) => (v ? null : "Escolha o tipo de administrador."),
  contact: (v) => (v.trim() ? null : "Indique um contacto (telefone ou email)."),
};

export function validateFields(names, values) {
  const errors = {};
  names.forEach((name) => {
    const message = rules[name]?.(values[name] ?? "", values);
    if (message) errors[name] = message;
  });
  return errors;
}