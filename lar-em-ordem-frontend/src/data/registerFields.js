//Tipos de conta possíveis no registo
export const accountTypes = [
  {
    role: "resident",
    label: "Morador",
    description: "Giro a minha casa e os meus consumos.",
  },
  {
    role: "service_provider",
    label: "Prestador de serviços",
    description: "Ofereço manutenção e assistência.",
  },
  {
    role: "partner",
    label: "Parceiro",
    description: "Empresa parceira de gestão.",
  },
];

//Campos especificos de cada tipo de conta
export const roleFields = {
  resident: [{ name: "nif", label: "NIF", type: "text" }],
  service_provider: [
    { name: "company_name", label: "Nome da Empresa", type: "text" },
    { name: "nif", label: "NIF da Empresa", type: "text" },
    { name: "phone", label: "Número de Telemóvel/Telefone", type: "tel" },
    { name: "provider_email", label: "Email de Contacto", type: "email" },
    { name: "description", label: "Descrição", type: "textarea" },
  ],
  partner: [
    { name: "nif", label: "NIF da Empresa", type: "text" },
    { name: "phone", label: "Número de Telemóvel/Telefone", type: "tel" },
    { name: "website", label: "Website", type: "url" },
    { name: "description", label: "Descrição", type: "textarea" },
  ],
};
