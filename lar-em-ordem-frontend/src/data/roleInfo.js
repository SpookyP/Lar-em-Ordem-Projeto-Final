//Como cada role, vinda de /api/user, se mostra na seleção do perfil

export const ROLE_INFO = {
  resident: { label: "Gestor de casas", hasHouses: true, home: "/dashboard" },
  service_provider: {
    label: "Prestador de Serviços",
    hasHouses: false,
    home: "/provider/requests",
  },
  partner: { label: "Parceiro", hasHouses: false },
  condominium_admin: { label: "Administrador de Condomínio", hasHouses: false },
};
