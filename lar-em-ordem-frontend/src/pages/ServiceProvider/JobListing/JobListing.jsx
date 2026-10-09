import { Droplet, Zap, Flame, ChevronLeft, ChevronRight } from "lucide-react";
import { OfferCard } from "../../../components/ui/OfferCard";
import { useState } from "react";

const PAGE_SIZE = 10;

const categories = {
  Canalização: { icon: Droplet, iconColor: "bg-teal-light text-teal" },
  Eletricidade: { icon: Zap, iconColor: "bg-amber-light text-amber" },
  Gás: { icon: Flame, iconColor: "bg-orange-light text-orange" },
};

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

function matchesAddress(address, query) {
  const text = addressText(address);
  return normalize(query)
  .split(" ")
  .every((token)=>text.includes(token));
}

// dados de exemplo, depois vêm do backend
const jobs = [
  {
    id: 1041,
    category: "Canalização",
    title: "Fuga de água na cozinha",
    description:
      "Humidade na parede por baixo do lava-loiça. Precisa de ser visto esta semana.",
    address: {
      district: "Porto",
      county: "Porto",
      location: "Porto",
      street: "Rua das Flores",
      door: "12",
      fraction: "3º Esq",
      postalCode: "4050-262",
    },
    postedAt: "Há 2 horas",
    budget: "80 – 150 €",
    urgent: true,
    offers: 2,
  },
  {
    id: 1040,
    category: "Eletricidade",
    title: "Substituir quadro elétrico",
    description: "Apartamento T2 com quadro antigo e sem diferencial.",
    address: {
      district: "Porto",
      county: "Matosinhos",
      location: "Senhora da Hora",
      street: "Avenida da República",
      door: "245",
      fraction: "R/C Dto",
      postalCode: "4460-123",
    },
    postedAt: "Há 5 horas",
    budget: "400 – 600 €",
    urgent: false,
    offers: 4,
  },
  {
    id: 1038,
    category: "Gás",
    title: "Revisão de caldeira",
    description: "Revisão anual da caldeira a gás, instalada em 2016.",
    address: {
      district: "Porto",
      county: "Vila Nova de Gaia",
      location: "Gaia",
      street: "Rua do Almada",
      door: "88",
      fraction: "",
      postalCode: "4400-110",
    },
    postedAt: "Ontem",
    budget: "60 – 90 €",
    urgent: false,
    offers: 1,
  },
  {
    id: 1037,
    category: "Canalização",
    title: "Instalar máquina de lavar loiça",
    description: "Já existe ponto de água, falta ligar e testar.",
    address: {
      district: "Porto",
      county: "Maia",
      location: "Águas Santas",
      street: "Rua Padre Américo",
      door: "7",
      fraction: "2º Dto",
      postalCode: "4425-123",
    },
    postedAt: "Ontem",
    budget: "50 – 80 €",
    urgent: false,
    offers: 0,
  },
  {
    id: 1034,
    category: "Eletricidade",
    title: "Tomadas sem corrente na sala",
    description:
      "Duas tomadas deixaram de funcionar depois de uma falha de luz.",
    address: {
      district: "Porto",
      county: "Porto",
      location: "Porto",
      street: "Rua de Santa Catarina",
      door: "310",
      fraction: "1º Fte",
      postalCode: "4000-442",
    },
    postedAt: "Há 2 dias",
    budget: "40 – 100 €",
    urgent: true,
    offers: 3,
  },
  {
    id: 1033,
    category: "Gás",
    title: "Cheiro a gás junto ao fogão",
    description: "Cheiro leve na cozinha. A válvula já foi fechada.",
    address: {
      district: "Porto",
      county: "Porto",
      location: "Porto",
      street: "Rua da Boavista",
      door: "56",
      fraction: "4º Esq",
      postalCode: "4050-114",
    },
    postedAt: "Há 2 dias",
    budget: "70 – 120 €",
    urgent: true,
    offers: 5,
  },
  {
    id: 1030,
    category: "Canalização",
    title: "Trocar autoclismo",
    description: "Autoclismo de embutir a perder água continuamente.",
    address: {
      district: "Porto",
      county: "Vila Nova de Gaia",
      location: "Mafamude",
      street: "Avenida da Liberdade",
      door: "19",
      fraction: "R/C",
      postalCode: "4430-123",
    },
    postedAt: "Há 3 dias",
    budget: "90 – 140 €",
    urgent: false,
    offers: 2,
  },
  {
    id: 1027,
    category: "Eletricidade",
    title: "Instalar carregador de carro elétrico",
    description: "Garagem individual, a 8 metros do quadro principal.",
    address: {
      district: "Porto",
      county: "Maia",
      location: "Maia",
      street: "Rua das Oliveiras",
      door: "3",
      fraction: "",
      postalCode: "4470-205",
    },
    postedAt: "Há 4 dias",
    budget: "600 – 900 €",
    urgent: false,
    offers: 6,
  },
  {
    id: 1025,
    category: "Gás",
    title: "Instalar placa a gás",
    description: "Substituição de placa elétrica por placa a gás de botija.",
    address: {
      district: "Porto",
      county: "Matosinhos",
      location: "Matosinhos",
      street: "Rua Brito Capelo",
      door: "150",
      fraction: "2º Dto",
      postalCode: "4450-072",
    },
    postedAt: "Há 5 dias",
    budget: "100 – 160 €",
    urgent: false,
    offers: 1,
  },
];

export default function JobListing() {
  const [search, setSearch] = useState("");
  const [addressSearch, setAddressSearch] = useState("");
  const [page, setPage] = useState(1);

  // sempre que um filtro muda, volta a pagina 1
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
      job.title.toLowerCase().includes(search.toLowerCase()) &&
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
                placeholder="Ex.: Porto, Rua das Flores, 4050"
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
            {visible.length === 0 ? (
              <p className="text-sm text-muted">
                Nenhum pedido encontrado. Experimente alterar os filtros.
              </p>
            ) : (
              <div className="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-2">
                {visible.map((job) => (
                  <OfferCard
                    key={job.id}
                    icon={categories[job.category].icon}
                    iconColor={categories[job.category].iconColor}
                    id={job.id}
                    title={job.title}
                    description={job.description}
                    location={job.address.location}
                    postedAt={job.postedAt}
                    budget={job.budget}
                    urgent={job.urgent}
                    offers={job.offers}
                    to={`/propostas/${job.id}`}
                  />
                ))}
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