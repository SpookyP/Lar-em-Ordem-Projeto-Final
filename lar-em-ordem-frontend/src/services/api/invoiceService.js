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
  let lastPage;

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

/**
 * Envia o PDF para extração dos dados; devolve o corpo da resposta.
 * A API só lê o ficheiro: não o guarda.
 */
export async function uploadInvoiceFile(formData) {
  // O browser define o Content-Type e o boundary do multipart.
  const response = await axiosClient.post("/api/v1/invoices/upload", formData);

  return getResponseBody(response);
}

/**
 * Cria a fatura com os respetivos consumos e itens de detalhe.
 * Com PDF, vai tudo num só pedido multipart: o ficheiro só é guardado se a fatura for registada.
 */
export async function createInvoice(payload, file = null) {
  const response = file
    ? await axiosClient.post("/api/v1/invoices", { ...payload, file }, {
        headers: { "Content-Type": "multipart/form-data" },
      })
    : await axiosClient.post("/api/v1/invoices", payload);

  return getResponseBody(response);
}

/**
 * Obtém o detalhe de uma fatura (consumos e itens incluídos).
 */
export async function getInvoice(id) {
  const response = await axiosClient.get(`/api/v1/invoices/${id}`);

  return getResponseBody(response);
}

/**
 * Obtém o corpo com o URL assinado do PDF associado à fatura.
 */
export async function getInvoiceFile(id) {
  const response = await axiosClient.get(`/api/v1/invoices/${id}/file`);

  return getResponseBody(response);
}

/**
 * Elimina uma fatura.
 */
export async function deleteInvoice(id) {
  await axiosClient.delete(`/api/v1/invoices/${id}`);
}


/**
 * Obtém a série anual de custos da habitação e dos benchmarks.
 */
export async function fetchAnnualBenchmarks(propertyId) {
  // Evita pedidos à API quando não existe uma propriedade selecionada.
  if (!propertyId) return null;

  const response = await axiosClient.get("/api/v1/benchmarks/annual/series", {
    params: {
      property_id: propertyId,
      service: "all",
    },
  });

  return getResponseBody(response);
}