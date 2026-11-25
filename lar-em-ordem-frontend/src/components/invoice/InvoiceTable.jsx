import { useMemo, useState } from "react";
import {
  deleteInvoice as deleteInvoiceRequest,
  getInvoice,
  getInvoiceFileBlob,
} from "../../services/api/invoiceService";
import {
  Search,
  FileText,
  RefreshCw,
  Zap,
  Droplets,
  Flame,
} from "lucide-react";
import { Pager } from "../ui/Pager";
import {
  HEADER_COLOR,
  FILTER_COLORS,
  getInvoiceTypes,
  formatCurrency,
  formatDate,
} from "./invoiceShared";
import { InvoiceTypeBadge } from "./InvoiceTypeBadge";
import { InvoiceActions } from "./InvoiceActions";
import { DeleteConfirmationModal } from "./DeleteConfirmationModal";
import { InvoiceDetailsModal } from "./InvoiceDetailsModal";

const PAGE_SIZE = 10;
const NO_INVOICES = [];

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
      : (startIndex ?? endIndex);

  const periodEnd =
    startIndex !== null && endIndex !== null
      ? Math.max(startIndex, endIndex)
      : (startIndex ?? endIndex);

  if (/^\d{4}$/.test(normalizedQuery)) {
    const year = Number(normalizedQuery);
    const yearStart = year * 12;
    const yearEnd = yearStart + 11;
    return periodStart <= yearEnd && periodEnd >= yearStart;
  }

  const monthYearMatch = normalizedQuery.match(/^(\d{1,2})[/-](\d{4})$/);

  if (monthYearMatch) {
    const month = Number(monthYearMatch[1]);
    const year = Number(monthYearMatch[2]);
    if (month < 1 || month > 12) return false;

    const searchedIndex = year * 12 + month - 1;
    return searchedIndex >= periodStart && searchedIndex <= periodEnd;
  }

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

export default function InvoiceTable({
  invoices = NO_INVOICES,
  loading = false,
  error = "",
  onRefresh,
}) {
  const invoiceList = Array.isArray(invoices) ? invoices : NO_INVOICES;

  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("all");
  const [actionLoading, setActionLoading] = useState("");

  const [paginationState, setPaginationState] = useState({
    source: invoiceList,
    page: 1,
  });

  const currentPage =
    paginationState.source === invoiceList ? paginationState.page : 1;

  const setCurrentPage = (page) => {
    setPaginationState({ source: invoiceList, page });
  };

  const [selectedInvoiceState, setSelectedInvoiceState] = useState({
    source: invoiceList,
    value: null,
  });

  const selectedInvoice =
    selectedInvoiceState.source === invoiceList
      ? selectedInvoiceState.value
      : null;

  const setSelectedInvoice = (value) => {
    setSelectedInvoiceState({ source: invoiceList, value });
  };

  const [deleteInvoiceState, setDeleteInvoiceState] = useState({
    source: invoiceList,
    value: null,
  });

  const deleteInvoice =
    deleteInvoiceState.source === invoiceList ? deleteInvoiceState.value : null;

  const setDeleteInvoice = (value) => {
    setDeleteInvoiceState({ source: invoiceList, value });
  };

  const [deleteConfirmedState, setDeleteConfirmedState] = useState({
    source: invoiceList,
    value: false,
  });

  const deleteConfirmed =
    deleteConfirmedState.source === invoiceList
      ? deleteConfirmedState.value
      : false;

  const setDeleteConfirmed = (value) => {
    setDeleteConfirmedState({ source: invoiceList, value });
  };

  const filteredInvoices = useMemo(() => {
    return invoiceList.filter((invoice) => {
      const query = search.trim().toLowerCase();
      const matchesText =
        String(invoice?.invoice_number ?? "")
          .toLowerCase()
          .includes(query) ||
        String(invoice?.supplier ?? "")
          .toLowerCase()
          .includes(query);
      const matchesDate = matchesDateSearch(invoice, query);
      const types = getInvoiceTypes(invoice);
      const matchesFilter = filter === "all" || types.includes(Number(filter));

      return (matchesText || matchesDate) && matchesFilter;
    });
  }, [invoiceList, search, filter]);

  const totalPages = Math.max(
    1,
    Math.ceil(filteredInvoices.length / PAGE_SIZE),
  );

  const page = Math.min(currentPage, totalPages);

  const paginatedInvoices = filteredInvoices.slice(
    (page - 1) * PAGE_SIZE,
    page * PAGE_SIZE,
  );

  const changeSearch = (value) => {
    setSearch(value);
    setCurrentPage(1);
  };

  const changeFilter = (value) => {
    setFilter(value);
    setCurrentPage(1);
  };

  const filterOptions = [
    {
      label: "Todas",
      value: "all",
      count: invoiceList.length,
      icon: FileText,
      color: FILTER_COLORS.all,
    },
    {
      label: "Eletricidade",
      value: "1",
      count: invoiceList.filter((invoice) =>
        getInvoiceTypes(invoice).includes(1),
      ).length,
      icon: Zap,
      color: FILTER_COLORS[1],
    },
    {
      label: "Água",
      value: "2",
      count: invoiceList.filter((invoice) =>
        getInvoiceTypes(invoice).includes(2),
      ).length,
      icon: Droplets,
      color: FILTER_COLORS[2],
    },
    {
      label: "Gás",
      value: "3",
      count: invoiceList.filter((invoice) =>
        getInvoiceTypes(invoice).includes(3),
      ).length,
      icon: Flame,
      color: FILTER_COLORS[3],
    },
  ];

  const handleDetails = async (invoice) => {
    setActionLoading(`details-${invoice.id}`);

    try {
      const body = await getInvoice(invoice.id);
      setSelectedInvoice(body);
    } catch (err) {
      console.error("Erro ao consultar fatura:", err);
      alert(
        err?.response?.data?.message ||
          "Não foi possível carregar os detalhes da fatura.",
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

    const pdfWindow = window.open("", "_blank");

    try {
      const blob = await getInvoiceFileBlob(invoice.id);
      const url = URL.createObjectURL(blob);

      if (pdfWindow) {
        pdfWindow.opener = null;
        pdfWindow.location.href = url;
      } else {
        const link = document.createElement("a");
        link.href = url;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.click();
      }

      window.setTimeout(() => URL.revokeObjectURL(url), 60000);
    } catch (err) {
      pdfWindow?.close();
      console.error("Erro ao obter PDF:", err);
      alert(
        err?.response?.data?.message ||
          err.message ||
          "Não foi possível abrir o PDF.",
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
      await deleteInvoiceRequest(deleteInvoice.id);

      if (String(selectedInvoice?.id) === String(deleteInvoice.id)) {
        setSelectedInvoice(null);
      }

      setDeleteInvoice(null);
      setDeleteConfirmed(false);

      if (onRefresh) {
        onRefresh();
      } else {
        window.dispatchEvent(new Event("invoicesUpdated"));
      }
    } catch (err) {
      console.error("Erro ao eliminar fatura:", err);
      alert(
        err?.response?.data?.message || "Não foi possível eliminar a fatura.",
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

          <div className="relative z-10 flex min-w-0 flex-wrap gap-2 sm:justify-end">
            {filterOptions.map((item) => {
              const Icon = item.icon;
              const active = filter === item.value;

              return (
                <button
                  key={item.value}
                  type="button"
                  onClick={() => changeFilter(item.value)}
                  className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                    active
                      ? "border-white bg-white shadow-sm"
                      : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                  }`}
                  style={{ color: active ? item.color : "#FFFFFF" }}
                >
                  <Icon size={15} />
                  {item.label}
                  <span
                    className={`rounded-full px-1.5 py-0.5 text-xs ${
                      active ? "bg-slate-100" : "bg-white/15 text-white/90"
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
          <div className="relative w-full min-w-0">
            <Search
              size={18}
              className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
            />
            <input
              type="text"
              value={search}
              onChange={(event) => changeSearch(event.target.value)}
              placeholder="Pesquisar por fornecedor, n.º da fatura ou data (ex.: 2026 ou 10/2025)..."
              className="block w-full min-w-0 max-w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#583D69]/50 focus:bg-white focus:ring-2 focus:ring-[#583D69]/10"
            />
          </div>
        </div>

        {/* List error message. */}
        {error && (
          <div className="mx-5 mb-4 min-w-0 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Cards for mobile and tablets. */}
        <div className="px-3 pb-3 xl:hidden">
          {loading ? (
            <div className="flex flex-col items-center justify-center py-12 text-center text-slate-500">
              <RefreshCw size={22} className="mb-2 animate-spin text-navy" />A
              carregar faturas...
            </div>
          ) : paginatedInvoices.length === 0 ? (
            <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 px-4 py-12 text-center">
              <div className="mb-3 rounded-full bg-navy/10 p-4">
                <FileText size={25} className="text-navy" />
              </div>
              <p className="font-semibold text-slate-700">
                Nenhuma fatura encontrada
              </p>
              <p className="mt-1 text-sm text-slate-400">
                Experimente alterar os filtros ou a pesquisa.
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {paginatedInvoices.map((invoice) => (
                <article
                  key={invoice.id}
                  className="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                  <div className="flex min-w-0 items-start justify-between gap-3">
                    <div className="min-w-0">
                      <InvoiceTypeBadge invoice={invoice} />
                    </div>
                    <span className="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500">
                      Fatura
                    </span>
                  </div>

                  <div className="mt-3 min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                      N.º da fatura
                    </p>
                    <p className="mt-1 wrap-break-word text-sm font-bold leading-5 text-slate-800">
                      {invoice.invoice_number || "—"}
                    </p>
                  </div>

                  <div className="mt-3 grid grid-cols-1 gap-3 min-[380px]:grid-cols-2">
                    <div className="min-w-0">
                      <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Fornecedor
                      </p>
                      <p className="mt-1 wrap-break-word text-sm text-slate-600">
                        {invoice.supplier || "—"}
                      </p>
                    </div>
                    <div className="min-w-0">
                      <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Período
                      </p>
                      <p className="mt-1 text-sm leading-5 text-slate-600">
                        {formatDate(invoice.period_start)}
                        <span className="mx-1 text-slate-400">—</span>
                        {formatDate(invoice.period_end)}
                      </p>
                    </div>
                  </div>

                  <div className="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                    <div className="min-w-0">
                      <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Valor total
                      </p>
                      <p className="mt-0.5 text-base font-bold text-slate-900">
                        {formatCurrency(invoice.total_amount)}
                      </p>
                    </div>
                    <InvoiceActions
                      invoice={invoice}
                      onDetails={handleDetails}
                      onDownload={handleDownload}
                      onDelete={openDeleteConfirmation}
                      disabled={actionLoading !== ""}
                    />
                  </div>
                </article>
              ))}
            </div>
          )}
        </div>

        {/* Desktop table. */}
        <div className="hidden w-full min-w-0 max-w-full overflow-x-auto overscroll-x-contain xl:block">
          <table className="w-full min-w-225 text-left text-sm">
            <thead
              className="text-xs uppercase tracking-wider text-white transition-colors duration-200"
              style={{ backgroundColor: HEADER_COLOR }}
            >
              <tr>
                <th className="px-5 py-4 font-semibold pl-10">Serviço</th>
                <th className="px-5 py-4 font-semibold text-center">
                  N.º da Fatura
                </th>
                <th className="px-5 py-4 font-semibold text-center">
                  Fornecedor
                </th>
                <th className="px-5 py-4 font-semibold text-center">Período</th>
                <th className="px-5 py-4 font-semibold text-center">
                  Valor Total
                </th>
                <th className="px-5 py-4 font-semibold text-center">Ações</th>
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
                      className="mx-auto mb-2 animate-spin text-navy"
                    />
                    A carregar faturas...
                  </td>
                </tr>
              ) : paginatedInvoices.length === 0 ? (
                <tr>
                  <td colSpan="6" className="px-5 py-14 text-center">
                    <div className="flex flex-col items-center">
                      <div className="mb-3 rounded-full bg-navy/10 p-4">
                        <FileText size={25} className="text-navy" />
                      </div>
                      <p className="font-semibold text-slate-700">
                        Nenhuma fatura encontrada
                      </p>
                      <p className="mt-1 text-sm text-slate-400">
                        Experimente alterar os filtros ou a pesquisa.
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
                    <td className="px-5 py-4 font-semibold text-slate-700 text-center">
                      {invoice.invoice_number || "—"}
                    </td>
                    <td className="px-5 py-4 text-slate-600 text-center">
                      {invoice.supplier || "—"}
                    </td>
                    <td className="px-5 py-4 text-slate-600 text-center">
                      <div className="whitespace-nowrap">
                        {formatDate(invoice.period_start)}
                        <span className="mx-1.5 text-slate-400">—</span>
                        {formatDate(invoice.period_end)}
                      </div>
                    </td>
                    <td className="px-5 py-4 font-bold text-slate-900 text-center">
                      {formatCurrency(invoice.total_amount)}
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center justify-end">
                        <InvoiceActions
                          invoice={invoice}
                          onDetails={handleDetails}
                          onDownload={handleDownload}
                          onDelete={openDeleteConfirmation}
                          disabled={actionLoading !== ""}
                        />
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {!loading && filteredInvoices.length > 0 && (
          <div className="flex min-w-0 flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-slate-500">
              A mostrar{" "}
              <span className="font-semibold text-slate-700">
                {(page - 1) * PAGE_SIZE + 1}
              </span>
              {" – "}
              <span className="font-semibold text-slate-700">
                {Math.min(page * PAGE_SIZE, filteredInvoices.length)}
              </span>
              {" de "}
              <span className="font-semibold text-slate-700">
                {filteredInvoices.length}
              </span>
              {" faturas"}
            </p>

            <Pager
              page={page}
              totalPages={totalPages}
              onChange={setCurrentPage}
              bordered={false}
            />
          </div>
        )}
      </div>

      <DeleteConfirmationModal
        invoice={deleteInvoice}
        confirmed={deleteConfirmed}
        loading={actionLoading === "delete"}
        onConfirmedChange={setDeleteConfirmed}
        onClose={closeDeleteConfirmation}
        onConfirm={confirmDelete}
      />

      <InvoiceDetailsModal
        invoice={selectedInvoice}
        onClose={() => setSelectedInvoice(null)}
      />
    </>
  );
}
