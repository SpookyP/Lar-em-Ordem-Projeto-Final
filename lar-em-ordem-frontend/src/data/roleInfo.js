// How each role from /api/user shows in the profile picker

export const ROLE_INFO = {
  resident: { label: "Gestor de casas", hasHouses: true, home: "/dashboard" },
  service_provider: {
    label: "Prestador de serviços",
    hasHouses: false,
    home: "/provider/requests",
  },
  partner: { label: "Parceiro", hasHouses: false, home: "/partner/offers" },
  condominium_admin: {
    label: "Administrador de condomínio",
    hasHouses: false,
    home: "/admin/condominiums",
  },
};

export const homePath = (profileName) =>
  ROLE_INFO[profileName]?.home ?? "/profiles";
