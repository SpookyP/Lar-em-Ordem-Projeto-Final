import { useState } from "react";
import { MapPin, Search } from "lucide-react";
import { OfferCard } from "../../../components/ui/OfferCard";
import { Pager } from "../../../components/ui/Pager";
import { TextInput } from "../../../components/form";
import { useAvailableJobs } from "../../../hooks/useAvailableJobs";
import { useDebouncedValue } from "../../../hooks/useDebouncedValue";
import { categoryInfo } from "../../../data/assistanceInfo";
import { formatRelative } from "../../../utils/date";

export default function JobListing() {
  const [search, setSearch] = useState("");
  const [location, setLocation] = useState("");

  const debouncedSearch = useDebouncedValue(search).trim();
  const debouncedLocation = useDebouncedValue(location).trim();
  const {
    items: jobs,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
  } = useAvailableJobs({
    search: debouncedSearch,
    location: debouncedLocation,
  });
  const hasFilters = Boolean(debouncedSearch || debouncedLocation);

  function clearFilters() {
    setSearch("");
    setLocation("");
  }

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-7">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal">
          Prestador de serviços
        </p>
        <h1 className="mt-1 font-display text-4xl text-navy">Pedidos disponíveis</h1>
        <p className="mt-2 text-sm text-muted">
          Pedidos de assistência abertos nas suas zonas e especialidades. Abra um pedido para
          ver a descrição e as fotografias e enviar a sua proposta.
        </p>
      </header>

      <div className="flex flex-col items-start gap-6 lg:flex-row">
        <aside className="flex w-full shrink-0 flex-col gap-5 rounded-2xl border border-border bg-card p-6 shadow-sm lg:w-72">
          <h2 className="text-lg font-semibold text-navy">Filtros</h2>
          <TextInput
            label="Pesquisa"
            icon={Search}
            value={search}
            onChange={setSearch}
            placeholder="Ex.: caldeira"
          />
          <TextInput
            label="Localidade"
            icon={MapPin}
            value={location}
            onChange={setLocation}
            placeholder="Ex.: Porto, Matosinhos"
          />
          {hasFilters && (
            <button
              type="button"
              onClick={clearFilters}
              className="self-start text-sm text-muted underline transition hover:text-navy"
            >
              Limpar filtros
            </button>
          )}
        </aside>

        <section className="flex w-full min-w-0 flex-1 flex-col gap-5 rounded-2xl border border-border bg-card p-7 shadow-sm">
          <div>
            <h2 className="text-lg font-semibold text-navy">Pedidos abertos</h2>
            <p className="text-sm text-muted">
              {total} {total === 1 ? "pedido disponível" : "pedidos disponíveis"}
            </p>
          </div>

          {loading ? (
            <p className="text-sm text-muted">A carregar...</p>
          ) : error ? (
            <p role="alert" className="text-sm text-red">
              {error}
            </p>
          ) : jobs.length === 0 ? (
            <p className="text-sm text-muted">
              {hasFilters
                ? "Nenhum pedido encontrado. Experimente alterar os filtros."
                : "Não há pedidos disponíveis para si neste momento."}
            </p>
          ) : (
            <div className="flex flex-col gap-2.5">
              {jobs.map((job) => {
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
                    to={`/provider/requests/${job.id}`}
                  />
                );
              })}
            </div>
          )}

          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </section>
      </div>
    </div>
  );
}