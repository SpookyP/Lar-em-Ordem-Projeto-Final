import { Briefcase, Building, Handshake, House, Wrench } from "lucide-react";
import { ROLES } from "../utils/roles";
import { formatPhone } from "../utils/format";

// who administers the building: a company that does it for a living, or one of the residents
export const ADMIN_TYPES = [
  {
    value: "professional",
    label: "Empresa de administração",
    description: "Administro condomínios como atividade profissional.",
    icon: Briefcase,
  },
  {
    value: "resident",
    label: "Condómino",
    description: "Moro no prédio e trato da administração.",
    icon: House,
  },
];

// account types offered on sign-up and in "Adicionar perfil"
export const accountTypes = [
  {
    role: ROLES.RESIDENT,
    label: "Morador",
    description: "Giro a minha casa e os meus consumos.",
    icon: House,
  },
  {
    role: ROLES.SERVICE_PROVIDER,
    label: "Prestador de serviços",
    description: "Ofereço manutenção e assistência.",
    icon: Wrench,
  },
  {
    role: ROLES.PARTNER,
    label: "Parceiro",
    description: "Empresa parceira de gestão.",
    icon: Handshake,
  },
  {
    role: ROLES.CONDOMINIUM_ADMIN,
    label: "Administrador de condomínio",
    description: "Giro um ou mais prédios: avisos, documentos e frações.",
    icon: Building,
  },
];

// profile fields per role (also used by "Os meus dados")
export const roleFields = {
  [ROLES.RESIDENT]: [
    { name: "nif", label: "NIF", type: "text", hint: "9 dígitos, sem espaços." },
  ],
  [ROLES.SERVICE_PROVIDER]: [
    { name: "company_name", label: "Nome da Empresa", type: "text" },
    { name: "nif", label: "NIF da Empresa", type: "text" },
    { name: "phone", label: "Número de Telemóvel/Telefone", type: "tel" },
    {
      name: "provider_email",
      label: "Email de Contacto",
      type: "email",
      optional: true,
      hint: "Opcional.",
    },
    {
      name: "description",
      label: "Descrição",
      type: "textarea",
      hint: "Que serviços presta e há quanto tempo.",
    },
  ],
  [ROLES.PARTNER]: [
    { name: "company_name", label: "Nome da Empresa", type: "text" },
    { name: "nif", label: "NIF da Empresa", type: "text" },
    { name: "phone", label: "Número de Telemóvel/Telefone", type: "tel" },
    {
      name: "website",
      label: "Website",
      type: "url",
      optional: true,
      hint: "Opcional. Ex.: https://www.empresa.pt",
    },
    {
      name: "description",
      label: "Descrição",
      type: "textarea",
      hint: "O que a empresa oferece aos moradores.",
    },
  ],
  [ROLES.CONDOMINIUM_ADMIN]: [
    {
      name: "admin_type",
      label: "Tipo de administrador",
      type: "choice",
      options: ADMIN_TYPES,
    },
    {
      name: "contact",
      label: "Contacto",
      type: "text",
      hint: "Telefone ou email que os condóminos podem usar.",
    },
  ],
};

// how a saved value is shown: phones grouped, choices by their label
export function displayValue(field, value) {
  if (field.type === "tel") return formatPhone(value);
  if (field.type === "choice") {
    return field.options.find((o) => o.value === value)?.label ?? value;
  }
  return value;
}

// how those fields are split into steps when a profile is created
export const detailSteps = {
  [ROLES.RESIDENT]: [
    {
      id: "resident",
      title: "Dados fiscais",
      description: "O NIF fica associado ao seu perfil de morador.",
      fields: ["nif"],
    },
  ],
  [ROLES.SERVICE_PROVIDER]: [
    {
      id: "company",
      title: "A sua empresa",
      description: "Como a empresa aparece aos moradores.",
      fields: ["company_name", "nif"],
    },
    {
      id: "contact",
      title: "Contacto e apresentação",
      description: "Como os clientes chegam até si.",
      fields: ["phone", "provider_email", "description"],
    },
  ],
  [ROLES.PARTNER]: [
    {
      id: "company",
      title: "A sua empresa",
      description: "Como a empresa aparece aos moradores.",
      fields: ["company_name", "nif"],
    },
    {
      id: "contact",
      title: "Contacto e apresentação",
      description: "Onde os moradores podem saber mais.",
      fields: ["phone", "website", "description"],
    },
  ],
  [ROLES.CONDOMINIUM_ADMIN]: [
    {
      id: "admin",
      title: "Administração",
      description: "Como se apresenta aos condóminos.",
      fields: ["admin_type", "contact"],
    },
  ],
};