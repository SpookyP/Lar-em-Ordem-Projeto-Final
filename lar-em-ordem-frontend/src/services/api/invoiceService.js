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

const PAGE_SIZE = 15;
const MAX_PAGES = 200; // Limite de segurança contra ciclos infinitos.

/**
 * Procura e agrega todas as páginas de faturas para uma determinada propriedade.
 */
export async function fetchAllInvoices(propertyId) {
  // Evita pedidos à API quando não existe uma propriedade selecionada.
  if (!propertyId) return [];

  const invoices = [];
  const seenIds = new Set();

  for (let page = 1; page <= MAX_PAGES; page += 1) {
    const response = await axiosClient.get("/api/v1/invoices", {
      params: {
        property_id: propertyId,
        per_page: PAGE_SIZE,
        page,
      },
    });

    // Normaliza a resposta e obtém as faturas da página atual.
    const body = getResponseBody(response);
    const pageInvoices = getResponseArray(body);

    // Página vazia: não há mais dados.
    if (pageInvoices.length === 0) break;

    // Se a API ignorar a paginação e repetir as mesmas faturas, termina.
    const allRepeated = pageInvoices.every(
      (invoice) => invoice?.id != null && seenIds.has(invoice.id)
    );

    if (allRepeated) break;

    pageInvoices.forEach((invoice) => {
      if (invoice?.id != null) seenIds.add(invoice.id);
    });

    invoices.push(...pageInvoices);

    // Número da última página indicado pela API (se existir).
    const lastPage = Number(
      body?.meta?.last_page ??
        body?.last_page ??
        body?.data?.meta?.last_page
    );

    if (Number.isFinite(lastPage) && lastPage >= 1) {
      if (page >= lastPage) break;
    } else if (pageInvoices.length < PAGE_SIZE) {
      // Sem metadados: uma página incompleta é a última.
      break;
    }
  }

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

  // Aceita tanto uma lista direta como a lista embrulhada em "data".
  return getResponseArray(getResponseBody(response));
}

/**
 * Envia o PDF para extração dos dados; devolve o corpo da resposta.
 * A API só lê o ficheiro: não o guarda.
 */
export async function uploadInvoiceFile(formData) {
  // O browser define o Content-Type e o boundary do multipart.
  const response = await axiosClient.post(
    "/api/v1/invoices/upload",
    formData
  );

  return getResponseBody(response);
}

/**
 * Cria a fatura com os respetivos consumos e itens de detalhe.
 * Com PDF, vai tudo num só pedido multipart: o ficheiro só é guardado se a fatura for registada.
 */
export async function createInvoice(payload) {
  const response = await axiosClient.post(
    "/api/v1/invoices",
    payload
  );

  return getResponseBody(response);
}

/**
 * Obtém o detalhe de uma fatura (consumos e itens incluídos).
 */
export async function getInvoice(id) {
  const response = await axiosClient.get(
    `/api/v1/invoices/${id}`
  );

  const body = getResponseBody(response);

  // Aceita a fatura diretamente ou embrulhada em "data".
  return body?.data ?? body;
}

/**
 * Obtém o corpo com o URL assinado do PDF associado à fatura.
 */
export async function getInvoiceFile(id) {
  const response = await axiosClient.get(
    `/api/v1/invoices/${id}/file`
  );

  return getResponseBody(response);
}

/**
 * Obtém o conteúdo binário (Blob) do PDF associado à fatura.
 */
export async function getInvoiceFileBlob(id) {
  const response = await axiosClient.get(
    `/api/v1/invoices/${id}/file/content`,
    { responseType: "blob" }
  );

  return response.data;
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

  const response = await axiosClient.get(
    "/api/v1/benchmarks/annual/series",
    {
      params: {
        property_id: propertyId,
        service: "all",
      },
    }
  );

  return getResponseBody(response);
}
