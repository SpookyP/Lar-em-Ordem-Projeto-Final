import axiosClient from "./axiosClient";

// Normalises the Axios response.
function getResponseBody(response) {
  return response?.data ?? response;
}

// Pulls the invoice list out of the API's different response formats.
function getResponseArray(body) {
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.data?.data)) return body.data.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;

  return [];
}

const PAGE_SIZE = 15;
const MAX_PAGES = 200; // Safety limit against endless loops.

/**
 * Fetches and merges every page of invoices for one property.
 */
export async function fetchAllInvoices(propertyId) {
  // Skips the API call when no property is selected.
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

    // Normalises the response and gets the current page's invoices.
    const body = getResponseBody(response);
    const pageInvoices = getResponseArray(body);

    // Empty page: no more data.
    if (pageInvoices.length === 0) break;

    // If the API ignores the pagination and repeats the same invoices, stop.
    const allRepeated = pageInvoices.every(
      (invoice) => invoice?.id != null && seenIds.has(invoice.id)
    );

    if (allRepeated) break;

    pageInvoices.forEach((invoice) => {
      if (invoice?.id != null) seenIds.add(invoice.id);
    });

    invoices.push(...pageInvoices);

    // Last page number reported by the API (if any).
    const lastPage = Number(
      body?.meta?.last_page ??
        body?.last_page ??
        body?.data?.meta?.last_page
    );

    if (Number.isFinite(lastPage) && lastPage >= 1) {
      if (page >= lastPage) break;
    } else if (pageInvoices.length < PAGE_SIZE) {
      // No metadata: an incomplete page is the last one.
      break;
    }
  }

  return invoices;
}

/**
 * Gets the monthly history of average costs for the comparable group.
 */
export async function fetchMonthlyCostBenchmarks(propertyId) {
  // Skips the API call when no property is selected.
  if (!propertyId) return [];

  const response = await axiosClient.get(
    "/api/v1/benchmarks/monthly/series",
    {
      params: {
        property_id: propertyId,
      },
    }
  );

  // Accepts a plain list or one wrapped in "data".
  return getResponseArray(getResponseBody(response));
}

/**
 * Sends the PDF so its data is extracted; returns the response body.
 * The API only reads the file and doesn't store it.
 */
export async function uploadInvoiceFile(formData) {
  // The browser sets the multipart Content-Type and boundary.
  const response = await axiosClient.post(
    "/api/v1/invoices/upload",
    formData
  );

  return getResponseBody(response);
}

/**
 * Creates the invoice with its consumptions and detail items.
 * With a PDF everything goes in one multipart request: the file is only stored if the invoice is saved.
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
 * Gets an invoice's detail (consumptions and items included).
 */
export async function getInvoice(id) {
  const response = await axiosClient.get(
    `/api/v1/invoices/${id}`
  );

  const body = getResponseBody(response);

  // Accepts the invoice directly or wrapped in "data".
  return body?.data ?? body;
}

/**
 * Gets the binary content (Blob) of the invoice's PDF.
 */
export async function getInvoiceFileBlob(id) {
  const response = await axiosClient.get(
    `/api/v1/invoices/${id}/file/content`,
    { responseType: "blob" }
  );

  return response.data;
}

/**
 * Deletes an invoice.
 */
export async function deleteInvoice(id) {
  await axiosClient.delete(`/api/v1/invoices/${id}`);
}

/**
 * Gets the yearly series of the property's costs and the benchmarks.
 */
export async function fetchAnnualBenchmarks(propertyId) {
  // Skips the API call when no property is selected.
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
