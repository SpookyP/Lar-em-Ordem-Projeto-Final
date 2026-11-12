import axiosClient from "./axiosClient";

// Normaliza a resposta recebida do Axios.
function getResponseBody(response) {
  return response?.data ?? response;
}

// Extrai a lista de faturas dos diferentes formatos de resposta da API.
function getResponseArray(body) {
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.data?.data)) return body.data.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;

  return [];
}

/**
 * Procura e agrega todas as páginas de faturas para uma determinada propriedade.
 */
export async function fetchAllInvoices(propertyId) {
  // Evita pedidos à API quando não existe uma propriedade selecionada.
  if (!propertyId) return [];

  const invoices = [];
  let currentPage = 1;
  let lastPage = 1;

  // Percorre todas as páginas e junta as respetivas faturas.
  do {
    const response = await axiosClient.get("/api/v1/invoices", {
      params: {
        property_id: propertyId,
        per_page: 100,
        page: currentPage,
      },
    });

    // Normaliza a resposta e obtém as faturas da página atual.
    const body = getResponseBody(response);
    const pageInvoices = getResponseArray(body);

    invoices.push(...pageInvoices);

    // Obtém o número da última página indicado pela API.
    const reportedLastPage = Number(
      body?.meta?.last_page ??
      body?.last_page ??
      body?.data?.meta?.last_page ??
      currentPage
    );

    // Interrompe a paginação se os dados ou os metadados forem inválidos.
    if (
      !Number.isFinite(reportedLastPage) ||
      reportedLastPage < currentPage ||
      pageInvoices.length === 0
    ) {
      break;
    }

    lastPage = reportedLastPage;
    currentPage += 1;

  } while (currentPage <= lastPage);

  return invoices;
}

/**
 * Obtém a série histórica mensal de custos médios do grupo comparável.
 */
export async function fetchMonthlyCostBenchmarks(propertyId) {
  // Evita pedidos à API quando não existe uma propriedade selecionada.
  if (!propertyId) return [];

  const response = await axiosClient.get(
    "/api/v1/benchmarks/monthly/series",
    {
      params: {
        property_id: propertyId,
      },
    }
  );

  const body = getResponseBody(response);

  // A API devolve diretamente uma lista de benchmarks.
  return Array.isArray(body) ? body : [];
}