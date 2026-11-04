import { detailSteps, roleFields } from "../../data/registerFields";
import { coverageToPayload } from "../ui/ProviderCoverageFields";
import { ROLES } from "../../utils/roles";

const ACCOUNT_STEP = {
  id: "account",
  kind: "account",
  title: "A sua conta",
  description: "Os dados com que vai entrar na plataforma.",
  fields: ["name", "email", "password", "password_confirmation"],
};

const TYPE_STEP = {
  id: "type",
  kind: "type",
  title: "Tipo de perfil",
  description: "Pode adicionar outros perfis mais tarde.",
  fields: ["role"],
};

const COVERAGE_STEPS = [
  {
    id: "zones",
    kind: "zones",
    title: "Onde trabalha",
    description: "Recebe pedidos dos concelhos que escolher.",
    fields: ["zone_ids"],
  },
  {
    id: "specialties",
    kind: "specialties",
    title: "O que faz",
    description: "Recebe pedidos das categorias destas especialidades.",
    fields: ["service_specialty_ids", "custom_specialties"],
  },
];

const REVIEW_STEP = {
  id: "review",
  kind: "review",
  title: "Confirme os dados",
  description: "Reveja tudo antes de continuar.",
  fields: [],
};

export function buildSteps({ withAccount, role }) {
  const details = (detailSteps[role] ?? []).map((step) => ({ ...step, kind: "details" }));

  return [
    ...(withAccount ? [ACCOUNT_STEP] : []),
    TYPE_STEP,
    ...details,
    ...(role === ROLES.SERVICE_PROVIDER ? COVERAGE_STEPS : []),
    REVIEW_STEP,
  ];
}

// only known keys go to the API, never the whole form state
export function buildPayload({ withAccount, values, role, coverage }) {
  const payload = { role };

  if (withAccount) {
    payload.name = values.name.trim();
    payload.email = values.email.trim();
    payload.password = values.password;
    payload.password_confirmation = values.password_confirmation;
  }

  (roleFields[role] ?? []).forEach((field) => {
    const value = String(values[field.name] ?? "").trim();
    payload[field.name] = field.optional && value === "" ? null : value;
  });

  if (role === ROLES.SERVICE_PROVIDER) {
    Object.assign(payload, coverageToPayload(coverage));
  }

  return payload;
}

// first step that owns a field the API rejected; the review step if none does
export function firstStepWithErrors(steps, errors) {
  const index = steps.findIndex((step) => step.fields.some((name) => errors[name]));
  return index === -1 ? steps.length - 1 : index;
}