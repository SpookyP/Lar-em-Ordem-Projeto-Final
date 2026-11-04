import { useEffect, useMemo, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import {
  Search,
  Building2,
  ChevronRight,
  ArrowRight,
  Phone,
  Globe,
} from "lucide-react";
import { getPartners, getPartner } from "../../../services/api/partnerService";
import { toHref } from "../../../utils/url";

const PALETTE = [
  { bg: "bg-navy", shadow: "shadow-navy/30" },
  { bg: "bg-orange", shadow: "shadow-orange/40" },
  { bg: "bg-teal", shadow: "shadow-teal/40" },
  { bg: "bg-navy-dark", shadow: "shadow-navy-dark/30" },
  { bg: "bg-red", shadow: "shadow-red/30" },
];

export default function Partners() {
  const [partners, setPartners] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");

  const [selectedId, setSelectedId] = useState(null);
  const [detail, setDetail] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const location = useLocation();

  useEffect(() => {
    let active = true;
    getPartners()
      .then((data) => {
        if (active) setPartners(data);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar os parceiros.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!selectedId) {
      setDetail(null);
      return;
    }
    let active = true;
    setDetailLoading(true);
    getPartner(selectedId)
      .then((data) => {
        if (active) setDetail(data);
      })
      .catch(() => {
        if (active) setDetail(null);
      })
      .finally(() => {
        if (active) setDetailLoading(false);
      });
    return () => {
      active = false;
    };
  }, [selectedId]);

  const colored = useMemo(
    () =>
      partners.map((p, i) => ({ ...p, theme: PALETTE[i % PALETTE.length] })),
    [partners],
  );
  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return colored;
    return colored.filter(
      (p) =>
        p.companyName?.toLowerCase().includes(q) ||
        p.description?.toLowerCase().includes(q),
    );
  }, [colored, query]);

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h1 className="font-display text-4xl text-teal">Parceiros</h1>
        <p className="font-display text-xl italic text-muted translate-y-6 sm:text-right">
          Empresas parceiras com vantagens para a tua casa.
        </p>
      </div>

      <div className="relative lg:w-1/2">
        <Search
          size={18}
          className="absolute left-3 top-1/2 -translate-y-1/2 text-muted"
        />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Procurar parceiro..."
          className="w-full rounded-xl border border-border bg-card py-2.5 pl-10 pr-4 text-sm outline-none focus:border-navy"
        />
      </div>

      {error && <p className="text-sm text-red">{error}</p>}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        <div
          className={`grid gap-6 ${selectedId ? "lg:grid-cols-2" : "grid-cols-1"}`}
        >
          <div className="min-w-0">
            {filtered.length === 0 ? (
              <p className="text-sm text-muted">Nenhum parceiro encontrado.</p>
            ) : (
              <div
                className={`grid gap-4 ${
                  selectedId
                    ? "grid-cols-1 xl:grid-cols-2"
                    : "sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                }`}
              >
                {filtered.map((p) => (
                  <button
                    key={p.id}
                    onClick={() => setSelectedId(p.id)}
                    className={`group flex h-full flex-col gap-3 rounded-2xl border bg-card p-5 text-left shadow-md ${p.theme.shadow} transition hover:-translate-y-0.5 ${
                      selectedId === p.id
                        ? "border-navy ring-1 ring-navy"
                        : "border-transparent"
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white ${p.theme.bg}`}
                      >
                        <Building2 size={22} />
                      </div>
                      <h3 className="min-w-0 font-display text-lg text-navy line-clamp-2">
                        {p.companyName}
                      </h3>
                    </div>
                    <p className="text-sm text-muted line-clamp-2 min-h-[2lh]">
                      {p.description}
                    </p>
                    <span className="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-navy transition-all group-hover:gap-2">
                      Ver ofertas <ArrowRight size={15} />
                    </span>
                  </button>
                ))}
              </div>
            )}
          </div>

          {selectedId && (
            <div className="min-w-0">
              {detailLoading ? (
                <p className="text-sm text-muted">A carregar ofertas...</p>
              ) : !detail ? (
                <p className="text-sm text-red">
                  Não foi possível carregar o parceiro.
                </p>
              ) : (
                <div className="flex flex-col gap-4 rounded-2xl bg-card p-6 shadow-sm">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <h2 className="font-display text-xl text-navy line-clamp-2">
                        {detail.companyName}
                      </h2>
                      <p className="text-sm text-muted line-clamp-3">
                        {detail.description}
                      </p>
                    </div>
                    <button
                      onClick={() => setSelectedId(null)}
                      className="shrink-0 text-sm text-muted hover:text-navy"
                      aria-label="Fechar"
                    >
                      ✕
                    </button>
                  </div>

                  {(detail.phone || detail.website) && (
                    <div className="flex flex-wrap gap-4 text-sm text-muted">
                      {detail.phone && (
                        <span className="flex items-center gap-1.5">
                          <Phone size={14} /> {detail.phone}
                        </span>
                      )}
                      {detail.website && (
                        <a
                          href={toHref(detail.website)}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="flex items-center gap-1.5 text-navy hover:underline"
                        >
                          <Globe size={14} /> Website
                        </a>
                      )}
                    </div>
                  )}

                  <h3 className="text-sm font-semibold text-muted">
                    Ofertas em vigor
                  </h3>

                  {detail.offers.length === 0 ? (
                    <p className="text-sm text-muted">
                      Sem ofertas em vigor de momento.
                    </p>
                  ) : (
                    <div className="flex flex-col gap-2">
                      {detail.offers.map((offer) => (
                        <Link
                          key={offer.id}
                          to={`/offers/${offer.id}`}
                          state={{ background: location }}
                          className="flex items-center justify-between gap-3 rounded-xl border border-border p-3 transition hover:border-navy/30 hover:shadow-sm"
                        >
                          <div className="min-w-0">
                            <div className="flex min-w-0 items-center gap-2">
                              <span className="shrink-0 rounded-full bg-teal/15 px-2 py-0.5 text-[10px] font-medium capitalize text-teal">
                                {offer.type}
                              </span>
                              <h4 className="font-medium text-navy wrap-break-word">
                                {offer.title}
                              </h4>
                            </div>
                            <p className="mt-0.5 text-xs text-muted line-clamp-4 wrap-break-word">
                              {offer.description}
                            </p>
                          </div>
                          <ChevronRight
                            size={16}
                            className="shrink-0 text-muted"
                          />
                        </Link>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
