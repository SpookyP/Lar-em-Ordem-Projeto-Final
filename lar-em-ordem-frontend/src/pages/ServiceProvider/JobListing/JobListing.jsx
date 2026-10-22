import { ChevronLeft, ChevronRight } from "lucide-react";
import { OfferCard } from "../../../components/ui/OfferCard";
import { useState, useEffect } from "react";
import { getAvailableJobs } from "../../../services/api/jobService";
import { categoryInfo } from "../../../data/assistanceInfo";
import { formatRelative } from "../../../utils/date";

const PAGE_SIZE = 10;



function getPages(page, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const result = [1];
  if (page > 3) result.push("…");
  for (let i = Math.max(2, page - 1); i <= Math.min(total - 1, page + 1); i++) {
    result.push(i);
  }
  if (page < total - 2) result.push("…");
  result.push(total);
  return result;
}

function normalize(text) {
  return text
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, " ")
    .trim();
}

function addressText(address) {
  return normalize(
    [
      address.district,
      address.location,
      address.county,
      address.street,
      address.door,
      address.fraction,
      address.postalCode,
    ]
      .filter(Boolean)
      .join(" "),
  );
}

function matchesQuery(text, query) {
  return normalize(query)
    .split(" ")
    .every((token) => text.includes(token));
}

function matchesAddress(address, query) {
  return matchesQuery(addressText(address), query);
}


export default function JobListing() {
  const [search, setSearch] = useState("");
  const [addressSearch, setAddressSearch] = useState("");
  const [page, setPage] = useState(1);
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getAvailableJobs()
      .then((list) => {
        if (active) setJobs(list);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar os pedidos.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  function applyFilter(setter, value) {
    setter(value);
    setPage(1);
  }

  function clearFilters() {
    setSearch("");
    setAddressSearch("");
    setPage(1);
  }

  const filtered = jobs.filter(
    (job) =>
      matchesQuery(normalize(`${job.title} ${job.description}`), search) &&
      matchesAddress(job.address, addressSearch),
  );

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const visible = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
  const pages = getPages(page, totalPages);

  return (
    // 8.5rem = footer (~4.25rem) + padding vertical do main (~4rem)
    // ajustar se o footer ou o padding do main mudarem
    <div className="flex min-h-0 flex-col gap-6 lg:h-[calc(100vh-8.5rem)]">
      <header className="shrink-0">
        <h1 className="font-display text-3xl text-navy">
          Propostas de Trabalho
        </h1>
      </header>

      <div className="flex min-h-0 flex-1 flex-col lg:flex-row gap-6">
        {/* filtros */}
        <div className="w-full lg:w-72 shrink-0">
          <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-5">
            <h2 className="font-display text-xl text-navy">Filtros</h2>

            <label className="flex flex-col gap-1 text-sm text-muted">
              Pesquisa
              <input
                type="text"
                value={search}
                onChange={(e) => applyFilter(setSearch, e.target.value)}
                placeholder="Ex.: caldeira"
                className="rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20"
              />
            </label>

            <label className="flex flex-col gap-1 text-sm text-muted">
              Localidade
              <input
                type="text"
                value={addressSearch}
                onChange={(e) => applyFilter(setAddressSearch, e.target.value)}
                placeholder="Ex.: Ex.: Porto, Matosinhos"
                className="rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20"
              />
            </label>

            <button
              onClick={clearFilters}
              className="self-start text-sm text-muted underline transition hover:text-navy"
            >
              Limpar filtros
            </button>
          </div>
        </div>

        <div className="flex min-h-0 flex-1 flex-col">
          <div className="flex min-h-0 flex-1 flex-col rounded-2xl bg-card p-8 shadow-sm">
            {/* cabecalho do cartao */}
            <div className="flex shrink-0 flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
              <div>
                <h2 className="font-display text-xl text-navy">
                  Pedidos abertos
                </h2>
                <p className="text-sm text-muted">
                  {filtered.length}{" "}
                  {filtered.length === 1
                    ? "pedido disponível"
                    : "pedidos disponíveis"}
                </p>
              </div>
            </div>

                        {/* lista de tickets */}
            {loading ? (
              <p className="text-sm text-muted">A carregar...</p>
            ) : error ? (
              <p className="text-sm text-red">{error}</p>
            ) : visible.length === 0 ? (
              <p className="text-sm text-muted">
                {jobs.length === 0
                  ? "Não há pedidos disponíveis para si neste momento."
                  : "Nenhum pedido encontrado. Experimente alterar os filtros."}
              </p>
            ) : (
              <div className="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-2">
                {visible.map((job) => {
                  const { icon, color } = categoryInfo(job.category.slug);
                  return (
                    <OfferCard
                      key={job.id}
                      icon={icon}
                      iconColor={color}
                      id={job.id.slice(-6).toUpperCase()}
                      title={job.title}
                      location={job.address.location}
                      postedAt={formatRelative(job.createdAt)}
                      to={`/teste-offer/${job.id}`}
                    />
                  );
                })}
              </div>
            )}

            {/* paginacao */}
            {totalPages > 1 && (
              <div className="flex shrink-0 items-center justify-center gap-2 mt-6">
                <button
                  onClick={() => setPage((p) => p - 1)}
                  disabled={page === 1}
                  aria-label="Página anterior"
                  className="rounded-full p-2 text-muted transition hover:text-navy disabled:opacity-40 disabled:hover:text-muted"
                >
                  <ChevronLeft size={18} />
                </button>

                {pages.map((n, i) =>
                  n === "…" ? (
                    <span key={`gap-${i}`} className="px-1 text-muted">
                      …
                    </span>
                  ) : (
                    <button
                      key={n}
                      onClick={() => setPage(n)}
                      className={`h-8 w-8 rounded-full text-sm transition ${
                        page === n
                          ? "bg-navy text-white"
                          : "text-muted hover:text-navy"
                      }`}
                    >
                      {n}
                    </button>
                  ),
                )}

                <button
                  onClick={() => setPage((p) => p + 1)}
                  disabled={page === totalPages}
                  aria-label="Página seguinte"
                  className="rounded-full p-2 text-muted transition hover:text-navy disabled:opacity-40 disabled:hover:text-muted"
                >
                  <ChevronRight size={18} />
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
