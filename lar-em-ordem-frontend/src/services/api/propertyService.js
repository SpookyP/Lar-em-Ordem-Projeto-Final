import axiosClient from "./axiosClient";

//Traduzir o vinculo que vem do back
const BOND_LABELS = {
  Owner: "Proprietário",
  Tenant: "Morador",
};

export async function getProperties(page = 1) {
  const response = await axiosClient.get(`/api/v1/properties?page=${page}`);

  //No corpo da response: data -> array com as casas e meta -> info da paginação
  //Percorre cada casa e cria um objeto na forma que o HouseList pede
  const houses = response.data.data.map((p) => ({
    id: p.id,
    label: p.fraction ? `${p.street}, ${p.fraction}` : p.street,
    location: p.location,
    bondLabel: BOND_LABELS[p.residentType] ?? p.residentType,
  }));

  return {
    houses,
    currentPage: response.data.meta.current_page,
    lastPage: response.data.meta.last_page,
    total: response.data.meta.total,
  };
}
