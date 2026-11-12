import { useEffect, useMemo, useState } from "react";
import axiosClient from "../../services/api/axiosClient";
import {
  Search,
  FileText,
  Eye,
  Download,
  Trash2,
  X,
  RefreshCw,
  Zap,
  Droplets,
  Flame,
  ChevronLeft,
  ChevronRight,
  AlertTriangle,
} from "lucide-react";

const PAGE_SIZE = 10;

// Cor fixa dos cabeçalhos: Eletricidade.
const HEADER_COLOR = "#1C3557";
const HEADER_DARK_COLOR = "#142840";

// Cores e estilos dos tipos de serviço.
const SERVICE_TYPES = {
  1: {
    name: "Eletricidade",
    icon: Zap,
    color: "#1C3557",
    iconColor: "text-white",
    background: "bg-[#1C3557]",
    border: "border-[#1C3557]",
    dot: "bg-[#1C3557]",
  },
  2: {
    name: "Água",
    icon: Droplets,
    color: "#0F766E",
    iconColor: "text-white",
    background: "bg-[#0F766E]",
    border: "border-[#0F766E]",
    dot: "bg-[#0F766E]",
  },
  3: {
    name: "Gás",
    icon: Flame,
    color: "#EA580C",
    iconColor: "text-white",
    background: "bg-[#EA580C]",
    border: "border-[#EA580C]",
    dot: "bg-[#EA580C]",
  },
};

// Cores dos filtros.
const FILTER_COLORS = {
  all: "#583D69",
  1: "#1C3557",
  2: "#0F766E",
  3: "#EA580A",
};

const DARKER_SERVICE_COLORS = {
  1: "#142840",
  2: "#0A514D",
  3: "#B84508",
};

function getConsumptions(invoice) {
  return Array.isArray(invoice?.consumptions) ? invoice.consumptions : [];
}

function getConsumptionItems(consumption) {
  const possibleItems = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return (
    possibleItems.find((items) => Array.isArray(items) && items.length > 0) ??
    possibleItems.find(Array.isArray) ??
    []
  );
}

function getTypeId(consumption) {
  const id =
    consumption?.consumption_type_id ?? consumption?.consumption_type?.id;

  if (id && SERVICE_TYPES[id]) return Number(id);

  const name = String(
    consumption?.consumption_type?.name ?? consumption?.type ?? ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;
  if (name.includes("água") || name.includes("agua")) return 2;
  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

function getInvoiceTypes(invoice) {
  return [
    ...new Set(getConsumptions(invoice).map(getTypeId).filter(Boolean)),
  ];
}

function toNumber(value) {
  if (value === null || value === undefined || value === "") return 0;

  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  let normalized = String(value).replace(/[€\s]/g, "");

  if (normalized.includes(",")) {
    normalized = normalized.replace(/\./g, "").replace(",", ".");
  }

  const number = Number(normalized);
  return Number.isFinite(number) ? number : 0;
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "EUR",
  }).format(toNumber(value));
}

function formatDate(value) {
  if (!value) return "—";

  const dateString = String(value).slice(0, 10);
  let date;

  const isoMatch = dateString.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  const ptMatch = dateString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

  if (isoMatch) {
    date = new Date(
      Number(isoMatch[1]),
      Number(isoMatch[2]) - 1,
      Number(isoMatch[3])
    );
  } else if (ptMatch) {
    date = new Date(
      Number(ptMatch[3]),
      Number(ptMatch[2]) - 1,
      Number(ptMatch[1])
    );
  } else {
    date = new Date(dateString);
  }

  if (Number.isNaN(date.getTime())) return value;

  return new Intl.DateTimeFormat("pt-PT").format(date);
}

function getMonthIndex(value) {
  if (!value) return null;

  const dateString = String(value).slice(0, 10);
  let year;
  let month;

  const isoMatch = dateString.match(/^(\d{4})-(\d{1,2})-\d{1,2}$/);
  const ptMatch = dateString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

  if (isoMatch) {
    year = Number(isoMatch[1]);
    month = Number(isoMatch[2]);
  } else if (ptMatch) {
    year = Number(ptMatch[3]);
    month = Number(ptMatch[2]);
  } else {
    return null;
  }

  if (month < 1 || month > 12) return null;

  return year * 12 + month - 1;
}

function matchesDateSearch(invoice, query) {
  const normalizedQuery = query.trim().replace(/\s+/g, "");

  if (!normalizedQuery) return true;

  const startIndex = getMonthIndex(invoice?.period_start);
  const endIndex = getMonthIndex(invoice?.period_end);

  if (startIndex === null && endIndex === null) return false;

  const periodStart =
    startIndex !== null && endIndex !== null
      ? Math.min(startIndex, endIndex)
      : startIndex ?? endIndex;

  const periodEnd =
    startIndex !== null && endIndex !== null
      ? Math.max(startIndex, endIndex)
      : startIndex ?? endIndex;

  // Pesquisa por ano: 2026.
  if (/^\d{4}$/.test(normalizedQuery)) {
    const year = Number(normalizedQuery);
    const yearStart = year * 12;
    const yearEnd = yearStart + 11;

    return periodStart <= yearEnd && periodEnd >= yearStart;
  }

  // Pesquisa por mês/ano: 09/2026 ou 09-2026.
  const monthYearMatch = normalizedQuery.match(/^(\d{1,2})[/-](\d{4})$/);

  if (monthYearMatch) {
    const month = Number(monthYearMatch[1]);
    const year = Number(monthYearMatch[2]);

    if (month < 1 || month > 12) return false;

    const searchedIndex = year * 12 + month - 1;
    return searchedIndex >= periodStart && searchedIndex <= periodEnd;
  }

  // Pesquisa por ano/mês: 2026-09 ou 2026/09.
  const yearMonthMatch = normalizedQuery.match(/^(\d{4})[/-](\d{1,2})$/);

  if (yearMonthMatch) {
    const year = Number(yearMonthMatch[1]);
    const month = Number(yearMonthMatch[2]);

    if (month < 1 || month > 12) return false;

    const searchedIndex = year * 12 + month - 1;
    return searchedIndex >= periodStart && searchedIndex <= periodEnd;
  }

  return false;
}

function getFileUrl(response) {
  const body = response?.data ?? response;

  if (typeof body === "string") return body;

  return (
    body?.url ??
    body?.file_url ??
    body?.signed_url ??
    body?.data?.url ??
    body?.data?.file_url ??
    body?.data?.signed_url ??
    null
  );
}

// Indicadores dos serviços associados à fatura.
function InvoiceTypeBadge({ invoice }) {
  const types = getInvoiceTypes(invoice);

  if (types.length === 0) {
    return (
      <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
        Não identificado
      </span>
    );
  }

  return (
    <div className="flex min-w-0 flex-wrap gap-2">
      {types.map((id) => {
        const service = SERVICE_TYPES[id];
        const Icon = service.icon;

        return (
          <span
            key={id}
            className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold text-white"
            style={{
              backgroundColor: service.color,
              borderColor: service.color,
              color: "#FFFFFF",
            }}
          >
            <Icon size={14} />
            {service.name}
          </span>
        );
      })}
    </div>
  );
}

export default function InvoiceTable({
  invoices = [],
  loading = false,
  error = "",
  onRefresh,
}) {
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedInvoice, setSelectedInvoice] = useState(null);
  const [deleteInvoice, setDeleteInvoice] = useState(null);
  const [deleteConfirmed, setDeleteConfirmed] = useState(false);
  const [actionLoading, setActionLoading] = useState("");

  const filteredInvoices = useMemo(() => {
    return invoices.filter((invoice) => {
      const query = search.trim().toLowerCase();

      const matchesText =
        String(invoice?.invoice_number ?? "").toLowerCase().includes(query) ||
        String(invoice?.supplier ?? "").toLowerCase().includes(query);

      const matchesDate = matchesDateSearch(invoice, query);
      const types = getInvoiceTypes(invoice);
      const matchesFilter = filter === "all" || types.includes(Number(filter));

      return (matchesText || matchesDate) && matchesFilter;
    });
  }, [invoices, search, filter]);

  const totalPages = Math.max(
    1,
    Math.ceil(filteredInvoices.length / PAGE_SIZE)
  );

  const paginatedInvoices = filteredInvoices.slice(
    (currentPage - 1) * PAGE_SIZE,
    currentPage * PAGE_SIZE
  );

  useEffect(() => {
    setCurrentPage(1);
    setSelectedInvoice(null);
    setDeleteInvoice(null);
    setDeleteConfirmed(false);
  }, [invoices]);

  useEffect(() => {
    setCurrentPage(1);
  }, [search, filter]);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  const filterOptions = [
    {
      label: "Todas",
      value: "all",
      count: invoices.length,
      icon: FileText,
      color: FILTER_COLORS.all,
    },
    {
      label: "Eletricidade",
      value: "1",
      count: invoices.filter((invoice) => getInvoiceTypes(invoice).includes(1))
        .length,
      icon: Zap,
      color: FILTER_COLORS[1],
    },
    {
      label: "Água",
      value: "2",
      count: invoices.filter((invoice) => getInvoiceTypes(invoice).includes(2))
        .length,
      icon: Droplets,
      color: FILTER_COLORS[2],
    },
    {
      label: "Gás",
      value: "3",
      count: invoices.filter((invoice) => getInvoiceTypes(invoice).includes(3))
        .length,
      icon: Flame,
      color: FILTER_COLORS[3],
    },
  ];

  const handleDetails = async (invoice) => {
    setActionLoading(`details-${invoice.id}`);

    try {
      const response = await axiosClient.get(`/api/v1/invoices/${invoice.id}`);
      const body = response?.data?.data ?? response?.data ?? response;
      setSelectedInvoice(body);
    } catch (err) {
      console.error("Erro ao consultar fatura:", err);
      alert(
        err?.response?.data?.message ||
          "Não foi possível carregar os detalhes da fatura."
      );
    } finally {
      setActionLoading("");
    }
  };

  const handleDownload = async (invoice) => {
    if (!invoice?.has_file) {
      alert("Esta fatura não tem nenhum PDF associado.");
      return;
    }

    setActionLoading(`file-${invoice.id}`);

    try {
      const response = await axiosClient.get(
        `/api/v1/invoices/${invoice.id}/file`
      );
      const url = getFileUrl(response);

      if (!url) {
        throw new Error("O servidor não devolveu o URL do ficheiro.");
      }

      window.open(url, "_blank", "noopener,noreferrer");
    } catch (err) {
      console.error("Erro ao obter PDF:", err);
      alert(
        err?.response?.data?.message ||
          err.message ||
          "Não foi possível abrir o PDF."
      );
    } finally {
      setActionLoading("");
    }
  };

  const openDeleteConfirmation = (invoice) => {
    setDeleteInvoice(invoice);
    setDeleteConfirmed(false);
  };

  const closeDeleteConfirmation = () => {
    if (actionLoading === "delete") return;

    setDeleteInvoice(null);
    setDeleteConfirmed(false);
  };

  const confirmDelete = async () => {
    if (!deleteInvoice || !deleteConfirmed) return;

    setActionLoading("delete");

    try {
      await axiosClient.delete(`/api/v1/invoices/${deleteInvoice.id}`);

      if (String(selectedInvoice?.id) === String(deleteInvoice.id)) {
        setSelectedInvoice(null);
      }

      setDeleteInvoice(null);
      setDeleteConfirmed(false);

      // Atualiza a lista após a eliminação da fatura.
      if (onRefresh) {
        onRefresh();
      } else {
        window.dispatchEvent(new Event("invoicesUpdated"));
      }
    } catch (err) {
      console.error("Erro ao eliminar fatura:", err);
      alert(
        err?.response?.data?.message ||
          "Não foi possível eliminar a fatura."
      );
    } finally {
      setActionLoading("");
    }
  };

  return (
    <>
      <div
        className="w-full min-w-0 max-w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        style={{ contain: "inline-size" }}
      >
        {/* Cabeçalho principal com filtros integrados. */}
        <header
          className="relative flex flex-col gap-4 overflow-hidden border-b border-white/20 p-5 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6"
          style={{ backgroundColor: HEADER_COLOR }}
        >
          <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
          <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />
          <div className="pointer-events-none absolute right-1/4 top-4 h-12 w-12 rounded-full border border-white/10" />

          <div className="relative z-10 min-w-0">
            <h2 className="text-lg font-bold tracking-tight text-white">
              Histórico de faturas
            </h2>
            <p className="mt-1 text-sm text-white/75">
              {filteredInvoices.length}{" "}
              {filteredInvoices.length === 1
                ? "fatura encontrada"
                : "faturas encontradas"}
            </p>
          </div>

          {/* Filtros de serviço à direita, no próprio cabeçalho. */}
          <div className="relative z-10 flex min-w-0 flex-wrap gap-2 sm:justify-end">
            {filterOptions.map((item) => {
              const Icon = item.icon;
              const active = filter === item.value;

              return (
                <button
                  key={item.value}
                  type="button"
                  onClick={() => setFilter(item.value)}
                  className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                    active
                      ? "border-white bg-white shadow-sm"
                      : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                  }`}
                  style={
                    active
                      ? {
                          color: item.color,
                        }
                      : {
                          color: "#FFFFFF",
                        }
                  }
                >
                  <Icon size={15} />
                  {item.label}
                  <span
                    className={`rounded-full px-1.5 py-0.5 text-xs ${
                      active
                        ? "bg-slate-100"
                        : "bg-white/15 text-white/90"
                    }`}
                  >
                    {item.count}
                  </span>
                </button>
              );
            })}
          </div>
        </header>

        <div className="w-full min-w-0 p-5">
          {/* Pesquisa */}
          <div className="relative w-full min-w-0">
            <Search
              size={18}
              className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
            />
            <input
              type="text"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Pesquisar por fornecedor, n.º da fatura ou data (ex.: 2026 ou 10/2025)..."
              className="block w-full min-w-0 max-w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#583D69]/50 focus:bg-white focus:ring-2 focus:ring-[#583D69]/10"
            />
          </div>
        </div>

        {/* Erro */}
        {error && (
          <div className="mx-5 mb-4 min-w-0 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Tabela */}
        <div className="block w-full min-w-0 max-w-full overflow-x-auto overscroll-x-contain">
          <table className="w-full min-w-[900px] text-left text-sm">
            <thead
              className="text-xs uppercase tracking-wider text-white transition-colors duration-200"
              style={{ backgroundColor: HEADER_COLOR }}
            >
              <tr>
                <th className="px-5 py-4 font-semibold">Serviço</th>
                <th className="px-5 py-4 font-semibold">N.º da Fatura</th>
                <th className="px-5 py-4 font-semibold">Fornecedor</th>
                <th className="px-5 py-4 font-semibold">Período</th>
                <th className="px-5 py-4 font-semibold">Valor Total</th>
                <th className="px-5 py-4 text-right font-semibold">Ações</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td
                    colSpan="6"
                    className="px-5 py-14 text-center text-slate-500"
                  >
                    <RefreshCw
                      size={20}
                      className="mx-auto mb-2 animate-spin text-[#1C3557]"
                    />
                    A carregar faturas...
                  </td>
                </tr>
              ) : paginatedInvoices.length === 0 ? (
                <tr>
                  <td colSpan="6" className="px-5 py-14 text-center">
                    <div className="flex flex-col items-center">
                      <div className="mb-3 rounded-full bg-[#1C3557]/10 p-4">
                        <FileText size={25} className="text-[#1C3557]" />
                      </div>
                      <p className="font-semibold text-slate-700">
                        Nenhuma fatura encontrada
                      </p>
                      <p className="mt-1 text-sm text-slate-400">
                        Experimenta alterar os filtros ou a pesquisa.
                      </p>
                    </div>
                  </td>
                </tr>
              ) : (
                paginatedInvoices.map((invoice) => (
                  <tr
                    key={invoice.id}
                    className="transition-colors duration-200 hover:bg-[#F8F7FA]"
                  >
                    <td className="px-5 py-4">
                      <InvoiceTypeBadge invoice={invoice} />
                    </td>
                    <td className="px-5 py-4 font-semibold text-slate-700">
                      {invoice.invoice_number || "—"}
                    </td>
                    <td className="px-5 py-4 text-slate-600">
                      {invoice.supplier || "—"}
                    </td>
                    <td className="px-5 py-4 text-slate-600">
                      <div className="whitespace-nowrap">
                        {formatDate(invoice.period_start)}
                        <span className="mx-1.5 text-slate-400">—</span>
                        {formatDate(invoice.period_end)}
                      </div>
                    </td>
                    <td className="px-5 py-4 font-bold text-slate-900">
                      {formatCurrency(invoice.total_amount)}
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          type="button"
                          title="Ver detalhes"
                          onClick={() => handleDetails(invoice)}
                          disabled={actionLoading !== ""}
                          className="rounded-lg p-2 text-slate-500 transition hover:bg-[#583D69]/10 hover:text-[#583D69] disabled:opacity-40"
                        >
                          <Eye size={17} />
                        </button>
                        <button
                          type="button"
                          title="Abrir PDF"
                          onClick={() => handleDownload(invoice)}
                          disabled={actionLoading !== "" || !invoice.has_file}
                          className="rounded-lg p-2 text-slate-500 transition hover:bg-[#1C3557]/10 hover:text-[#1C3557] disabled:cursor-not-allowed disabled:opacity-30"
                        >
                          <Download size={17} />
                        </button>
                        <button
                          type="button"
                          title="Eliminar fatura"
                          onClick={() => openDeleteConfirmation(invoice)}
                          disabled={actionLoading !== ""}
                          className="rounded-lg p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                        >
                          <Trash2 size={17} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Paginação */}
        {!loading && filteredInvoices.length > 0 && (
          <div className="flex min-w-0 flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-slate-500">
              A mostrar{" "}
              <span className="font-semibold text-slate-700">
                {(currentPage - 1) * PAGE_SIZE + 1}
              </span>
              {" – "}
              <span className="font-semibold text-slate-700">
                {Math.min(currentPage * PAGE_SIZE, filteredInvoices.length)}
              </span>
              {" de "}
              <span className="font-semibold text-slate-700">
                {filteredInvoices.length}
              </span>
              {" faturas"}
            </p>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setCurrentPage((page) => Math.max(1, page - 1))}
                disabled={currentPage === 1}
                className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-[#583D69]/30 hover:bg-[#583D69]/5 hover:text-[#583D69] disabled:cursor-not-allowed disabled:opacity-40"
              >
                <ChevronLeft size={16} />
                Anterior
              </button>

              <span className="min-w-[90px] text-center text-sm font-semibold text-slate-700">
                Página {currentPage} / {totalPages}
              </span>

              <button
                type="button"
                onClick={() =>
                  setCurrentPage((page) => Math.min(totalPages, page + 1))
                }
                disabled={currentPage === totalPages}
                className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-[#583D69]/30 hover:bg-[#583D69]/5 hover:text-[#583D69] disabled:cursor-not-allowed disabled:opacity-40"
              >
                Seguinte
                <ChevronRight size={16} />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Confirmação de eliminação */}
      {deleteInvoice && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-5 flex items-start justify-between">
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-red-50 p-3 text-red-600">
                  <AlertTriangle size={23} />
                </div>
                <div>
                  <h3 className="text-lg font-bold text-slate-900">
                    Eliminar fatura
                  </h3>
                  <p className="mt-1 text-sm text-slate-500">
                    Confirma que pretendes continuar.
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={closeDeleteConfirmation}
                disabled={actionLoading === "delete"}
                className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
              >
                <X size={19} />
              </button>
            </div>

            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Fatura selecionada</p>
              <p className="mt-1 font-semibold text-slate-900">
                {deleteInvoice.invoice_number || "Sem número"}
              </p>
              <p className="mt-1 text-sm text-slate-600">
                {deleteInvoice.supplier || "Fornecedor não identificado"}
              </p>
              <p className="mt-2 font-bold text-slate-900">
                {formatCurrency(deleteInvoice.total_amount)}
              </p>
            </div>

            <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-red-200 hover:bg-red-50/40">
              <input
                type="checkbox"
                checked={deleteConfirmed}
                onChange={(event) => setDeleteConfirmed(event.target.checked)}
                disabled={actionLoading === "delete"}
                className="mt-0.5 h-5 w-5 shrink-0 cursor-pointer accent-red-600"
              />
              <span className="text-sm leading-5 text-slate-700">
                Confirmo que quero eliminar esta fatura e compreendo que esta
                ação não pode ser desfeita.
              </span>
            </label>

            <div className="mt-6 flex gap-3">
              <button
                type="button"
                onClick={closeDeleteConfirmation}
                disabled={actionLoading === "delete"}
                className="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={confirmDelete}
                disabled={!deleteConfirmed || actionLoading === "delete"}
                className="flex-1 rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300"
              >
                {actionLoading === "delete" ? "A eliminar..." : "Eliminar fatura"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detalhes da fatura */}
      {selectedInvoice &&
        (() => {
          const invoiceTypes = getInvoiceTypes(selectedInvoice);
          const primaryService = SERVICE_TYPES[invoiceTypes[0]];
          const HeaderIcon = primaryService?.icon || FileText;

          // Cabeçalho, total e botão Fechar sempre com azul da Eletricidade.
          const headerColor = HEADER_COLOR;
          const darkerHeaderColor = HEADER_DARK_COLOR;

          const consumptions = getConsumptions(selectedInvoice);

          return (
            <div
              className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
              onClick={() => setSelectedInvoice(null)}
            >
              <div
                className="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                onClick={(event) => event.stopPropagation()}
              >
                {/* Cabeçalho fixo em azul da Eletricidade */}
                <div
                  className="relative overflow-hidden p-6"
                  style={{ backgroundColor: headerColor }}
                >
                  {/* Elementos decorativos */}
                  <div
                    className="pointer-events-none absolute -right-8 -top-12 h-44 w-44 rounded-full opacity-20"
                    style={{ backgroundColor: darkerHeaderColor }}
                  />
                  <div
                    className="pointer-events-none absolute -bottom-16 right-28 h-36 w-36 rounded-full opacity-20"
                    style={{ backgroundColor: darkerHeaderColor }}
                  />

                  <div className="relative flex items-start justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-4">
                      <div
                        className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm"
                        style={{ backgroundColor: darkerHeaderColor }}
                      >
                        <HeaderIcon size={27} />
                      </div>
                      <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-[0.16em] text-white/70">
                          Histórico de faturação
                        </p>
                        <h3 className="mt-1 text-xl font-bold text-white">
                          Detalhes da fatura
                        </h3>
                        <p className="mt-1 truncate text-sm text-white/80">
                          {selectedInvoice.invoice_number || "Sem número de fatura"}
                        </p>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => setSelectedInvoice(null)}
                      aria-label="Fechar detalhes da fatura"
                      className="shrink-0 rounded-xl bg-white/10 p-2 text-white transition hover:bg-white/20"
                    >
                      <X size={20} />
                    </button>
                  </div>

                  {/* Serviços associados à fatura */}
                  <div className="relative mt-5 flex flex-wrap gap-2">
                    {invoiceTypes.length > 0 ? (
                      invoiceTypes.map((typeId) => {
                        const service = SERVICE_TYPES[typeId];
                        const Icon = service.icon;

                        return (
                          <span
                            key={typeId}
                            className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white"
                          >
                            <Icon size={14} />
                            {service.name}
                          </span>
                        );
                      })
                    ) : (
                      <span className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                        <FileText size={14} />
                        Serviço não identificado
                      </span>
                    )}
                  </div>
                </div>

                {/* Conteúdo do modal */}
                <div className="max-h-[calc(85vh-150px)] w-full min-w-0 space-y-6 overflow-y-auto p-5 sm:p-6">
                  {/* Informação principal */}
                  <section>
                    <h4 className="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">
                      Informação da fatura
                    </h4>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p className="text-xs font-medium text-slate-500">
                          Fornecedor
                        </p>
                        <p className="mt-1 break-words font-semibold text-slate-900">
                          {selectedInvoice.supplier || "—"}
                        </p>
                      </div>
                      <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p className="text-xs font-medium text-slate-500">
                          Número da fatura
                        </p>
                        <p className="mt-1 break-words font-semibold text-slate-900">
                          {selectedInvoice.invoice_number || "—"}
                        </p>
                      </div>
                      <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p className="text-xs font-medium text-slate-500">
                          Data de emissão
                        </p>
                        <p className="mt-1 font-semibold text-slate-900">
                          {formatDate(selectedInvoice.issue_date)}
                        </p>
                      </div>
                      <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p className="text-xs font-medium text-slate-500">
                          Período de faturação
                        </p>
                        <p className="mt-1 font-semibold text-slate-900">
                          {formatDate(selectedInvoice.period_start)}
                          <span className="mx-1.5 text-slate-400">—</span>
                          {formatDate(selectedInvoice.period_end)}
                        </p>
                      </div>
                    </div>
                  </section>

                  {/* Consumos associados */}
                  <section>
                    <div className="mb-3 flex items-center justify-between gap-3">
                      <h4 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                        Consumos associados
                      </h4>
                      <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                        {consumptions.length}{" "}
                        {consumptions.length === 1 ? "consumo" : "consumos"}
                      </span>
                    </div>

                    {consumptions.length === 0 ? (
                      <div className="rounded-xl border border-dashed border-slate-200 p-5 text-center">
                        <FileText
                          className="mx-auto mb-2 text-slate-300"
                          size={24}
                        />
                        <p className="text-sm text-slate-500">
                          Não existem consumos associados a esta fatura.
                        </p>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {consumptions.map((consumption, index) => {
                          const typeId = getTypeId(consumption);
                          const service = SERVICE_TYPES[typeId];
                          const Icon = service?.icon || FileText;
                          const serviceColor = service?.color || "#64748B";
                          const darkerColor =
                            DARKER_SERVICE_COLORS[typeId] || "#475569";
                          const items = getConsumptionItems(consumption);

                          return (
                            <article
                              key={consumption.id || `${typeId || "service"}-${index}`}
                              className="overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-sm"
                            >
                              <div className="flex items-center justify-between gap-3 p-4">
                                <div className="flex min-w-0 items-center gap-3">
                                  <div
                                    className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                                    style={{ backgroundColor: serviceColor }}
                                  >
                                    <Icon size={19} />
                                  </div>
                                  <div className="min-w-0">
                                    <p className="font-semibold text-slate-800">
                                      {service?.name || "Consumo"}
                                    </p>
                                    <p className="mt-1 text-xs text-slate-500">
                                      {formatDate(consumption.period_start)}
                                      {" — "}
                                      {formatDate(consumption.period_end)}
                                    </p>
                                  </div>
                                </div>
                                <div className="shrink-0 text-right">
                                  <p className="text-xs text-slate-500">Custo</p>
                                  <p className="mt-0.5 font-bold text-slate-900">
                                    {formatCurrency(consumption.cost)}
                                  </p>
                                </div>
                              </div>

                              {items.length > 0 && (
                                <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                                  <p
                                    className="mb-2 text-xs font-semibold uppercase tracking-wider"
                                    style={{ color: darkerColor }}
                                  >
                                    Detalhe do consumo
                                  </p>
                                  <div className="space-y-2">
                                    {items.map((item, itemIndex) => (
                                      <div
                                        key={item.id || itemIndex}
                                        className="flex items-start justify-between gap-3 text-sm"
                                      >
                                        <span className="min-w-0 text-slate-600">
                                          {item.name || "Item"}
                                          {item.amount != null && (
                                            <span className="ml-1 text-xs text-slate-400">
                                              ({item.amount} {item.unit || ""})
                                            </span>
                                          )}
                                        </span>
                                        <span className="shrink-0 font-medium text-slate-800">
                                          {formatCurrency(item.cost)}
                                        </span>
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </article>
                          );
                        })}
                      </div>
                    )}
                  </section>

                  {/* Total da fatura */}
                  <div
                    className="flex items-center justify-between gap-4 rounded-xl p-4 text-white"
                    style={{ backgroundColor: headerColor }}
                  >
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wider text-white/70">
                        Total da fatura
                      </p>
                      <p className="mt-1 text-sm text-white/85">
                        Valor indicado pelo fornecedor
                      </p>
                    </div>
                    <p className="shrink-0 text-xl font-bold">
                      {formatCurrency(selectedInvoice.total_amount)}
                    </p>
                  </div>

                  <div className="flex justify-end">
                    <button
                      type="button"
                      onClick={() => setSelectedInvoice(null)}
                      className="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                      style={{ backgroundColor: headerColor }}
                    >
                      Fechar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          );
        })()}
    </>
  );
}