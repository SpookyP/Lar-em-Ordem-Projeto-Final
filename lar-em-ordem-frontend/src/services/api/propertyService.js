import axiosClient from "./axiosClient";
import { cleanParams, toPage } from "./pagination";

//Traduzir o vinculo que vem do back
const BOND_LABELS = {
  Owner: "Proprietário",
  Tenant: "Morador",
};

const PROPERTY_TYPE_LABELS = {
  Apartment: "Apartamento",
  House: "Moradia",
};

function toHouse(p) {
  return {
    id: p.id,
    label: p.fraction ? `${p.street}, ${p.fraction}` : p.street,
    location: p.location,
    bondLabel: BOND_LABELS[p.residentType] ?? p.residentType,
  };
}

export async function getPropertiesPage({ page = 1, perPage = 5 } = {}) {
  const response = await axiosClient.get("/api/v1/properties", {
    params: cleanParams({ page, per_page: perPage }),
  });
  return toPage(response, toHouse);
}

export async function getProperties(page = 1) {
  const response = await axiosClient.get(`/api/v1/properties?page=${page}`);
  const houses = response.data.data.map(toHouse);

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

export async function getProperty(id) {
  const response = await axiosClient.get(`/api/v1/properties/${id}`);
  const p = response.data.data;

  const address = p.address ?? {};
  const contract = p.contracts?.[0] ?? null;
  const bondName = contract?.residentType?.type;

  return {
    id: p.id,
    area: p.area,
    propertyTypeId: p.propertyType?.id ?? "",
    typologyId: p.propertyTypology?.id ?? "",
    residentTypeId: contract?.residentType?.id ?? "",
    address: {
      street: address.street,
      door: address.door,
      fraction: address.fraction,
      postalCode: address.postalCode,
      location: address.location,
      county: address.county,
      district: address.district,
    },
    typeLabel:
      PROPERTY_TYPE_LABELS[p.propertyType?.type] ?? p.propertyType?.type,
    typologyLabel: p.propertyTypology?.typology,
    bondLabel: BOND_LABELS[bondName] ?? bondName,
    startDate: contract?.startDate ?? null,
    endDate: contract?.endDate ?? null,
  };
}

export async function updateProperty(id, data) {
  const response = await axiosClient.put(`/api/v1/properties/${id}`, data);
  return response.data;
}

export async function deleteProperty(id) {
  await axiosClient.delete(`/api/v1/properties/${id}`);
}
