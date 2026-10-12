import { useState } from "react";
import { useParams, Link } from "react-router-dom";
import {
  ChevronLeft,
  Droplet,
  Zap,
  Flame,
  MapPin,
  Calendar,
} from "lucide-react";

const categories = {
  Canalizacao: { icon: Droplet, iconColor: "bg-teal-light text-teal" },
  Eletricidade: { icon: Zap, iconColor: "bg-amber-light text-amber" },
  Gas: { icon: Flame, iconColor: "bg-orange-light text-orange" },
};

const mockJob = {
  id: 1041,
  category: "Canalizacao",
  title: "Reparação de tubagem partida do sistema AVAC",
  description:
    "O tubo de água fria debaixo do lavatório partiu durante a noite. Há água a escorrer continuamente. Precisa de reparação urgente — já fechei a torneira geral. Habitação com acesso fácil, 2.º andar com elevador.",
  address: {
    district: "Porto",
    county: "Porto",
    location: "Lisboa",
    street: "Rua de Alfama 23, 2.º Esq",
    postalCode: "1100-000",
  },
  client: {
    name: "Carlos Silva",
    initials: "CS",
  },
  postedAt: "31 Jul 2026",
  budget: "120 €",
};

export default function Offer() {
  const { id } = useParams();

  const [price, setPrice] = useState("");
  const [availability, setAvailability] = useState("");
  const [note, setNote] = useState("");

  const categoryInfo =
    categories[mockJob.category] || categories["Canalizacao"];
  const IconComponent = categoryInfo.icon;

  function handleSubmit(e) {
    e.preventDefault();
    console.log({ id, price, availability, note });
  }

  return (
    <div className="flex min-h-0 flex-1 flex-col gap-6">
      <div className="flex shrink-0 items-center gap-4">
        <Link
          to={-1}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card shadow-sm text-muted transition hover:text-navy"
          aria-label="Voltar"
        >
          <ChevronLeft size={20} />
        </Link>
        <div>
          <h1 className="font-display text-2xl text-navy">Detalhe do Pedido</h1>
          <p className="text-sm text-muted">
            JOB-{mockJob.id} · Publicado a {mockJob.postedAt}
          </p>
        </div>
      </div>

      <div className="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto pr-2 pb-6">
        {/* Banner com Categoria */}
        <div className="relative flex shrink-0 items-end justify-between overflow-hidden rounded-2xl bg-navy p-8 text-white min-h-[140px] shadow-sm">
          <div className="absolute right-6 top-1/2 -translate-y-1/2 opacity-10">
            <IconComponent size={120} />
          </div>
          <span
            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${categoryInfo.iconColor}`}
          >
            <IconComponent size={14} />
            {mockJob.category}
          </span>
        </div>

        {/* Detalhes do Pedido */}
        <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6 shrink-0">
          <div className="flex flex-col gap-2">
            <h2 className="font-display text-xl text-navy">{mockJob.title}</h2>

            <div className="flex flex-wrap items-center gap-4 text-sm text-muted">
              <span className="flex items-center gap-1.5">
                <MapPin size={16} />
                {mockJob.address.street}, {mockJob.address.location}
              </span>
              <span className="flex items-center gap-1.5">
                <Calendar size={16} />
                Publicado a {mockJob.postedAt}
              </span>
            </div>
          </div>

          {/* Info do Cliente */}
          <div className="flex items-center gap-3 border-t border-surface pt-4">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-navy text-sm font-semibold text-white">
              {mockJob.client.initials}
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-semibold text-navy">
                {mockJob.client.name}
              </span>
            </div>
          </div>

          {/* Descrição */}
          <div className="flex flex-col gap-2 border-t border-surface pt-4">
            <h3 className="text-sm font-semibold text-muted">Descrição</h3>
            <p className="text-sm text-navy leading-relaxed">
              {mockJob.description}
            </p>
          </div>
        </div>

        {/* Formulário para Enviar Proposta */}
        <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6 shrink-0">
          <h2 className="font-display text-xl text-navy">Enviar Proposta</h2>

          <form onSubmit={handleSubmit} className="flex flex-col gap-5">
            <label className="flex flex-col gap-1 text-sm text-muted">
              Preço estimado (€) *
              <input
                type="text"
                value={price}
                onChange={(e) => setPrice(e.target.value)}
                placeholder="ex: 120"
                className="rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20"
                required
              />
            </label>

            <label className="flex flex-col gap-1 text-sm text-muted">
              Disponibilidade *
              <input
                type="date"
                value={availability}
                onChange={(e) => setAvailability(e.target.value)}
                className="rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20"
                required
              />
            </label>

            <label className="flex flex-col gap-1 text-sm text-muted">
              Nota para o cliente
              <textarea
                rows={4}
                value={note}
                onChange={(e) => setNote(e.target.value)}
                placeholder="Apresente-se brevemente e indique a sua abordagem à reparação..."
                className="rounded-lg bg-surface px-3 py-2 text-sm text-navy outline-none focus:ring-2 focus:ring-navy/20 resize-none"
              />
            </label>

            <button
              type="submit"
              className="rounded-xl bg-navy px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
            >
              Submeter Proposta
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
