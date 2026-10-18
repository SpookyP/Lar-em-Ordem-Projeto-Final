import axiosClient from "./axiosClient";

//Traduzir o vinculo que vem do back
const BOND_LABELS = {
  Owner: "Proprietário",
  Tenant: "Morador",
};

const PROPERTY_TYPE_LABELS = {
  Apartment: "Apartamento",
  House: "Moradia",
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

export async function getFormOptions() {
  const response = await axiosClient.get("/api/v1/properties/forms-options");
  const { propertyTypes, propertyTypologies, residentTypes } =
    response.data.data;

  return {
    propertyTypes: propertyTypes.map((t) => ({
      id: t.id,
      label: PROPERTY_TYPE_LABELS[t.type] ?? t.type,
    })),
    typologies: propertyTypologies.map((t) => ({
      id: t.id,
      label: t.typology,
    })),
    residentTypes: residentTypes.map((t) => ({
      id: t.id,
      label: BOND_LABELS[t.type] ?? t.type,
    })),
  };
}

export async function createProperty(data) {
  const response = await axiosClient.post("/api/v1/properties", data);
  return response.data;
}

export async function getAllProperties() {
  const first = await getProperties(1);
  let all = first.houses;
  for (let page = 2; page <= first.lastPage; page++) {
    const next = await getProperties(page);
    all = all.concat(next.houses);
  }
  return all;
}
