import { Link, useLocation } from "react-router-dom";
import { Tag, ArrowRight } from "lucide-react";
import { formatDate } from "../../utils/date";

const PALETTE = [
  { bg: "bg-navy", shadow: "shadow-navy/30" },
  { bg: "bg-orange", shadow: "shadow-orange/40" },
  { bg: "bg-[#583D69]", shadow: "shadow-[#583D69]/30" },
  { bg: "bg-teal", shadow: "shadow-teal/40" },
  { bg: "bg-red", shadow: "shadow-red/30" },
];

export function OfferGridCard({ offer, index = 0 }) {
  const location = useLocation();
  const theme = PALETTE[index % PALETTE.length];

  return (
    <Link
      to={`/offers/${offer.id}`}
      state={{ background: location }}
      className={`group flex h-full flex-col gap-3 rounded-2xl border border-transparent bg-card p-5 shadow-md ${theme.shadow} transition hover:-translate-y-0.5`}
    >
      <div className="flex items-center gap-3">
        <div
          className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white ${theme.bg}`}
        >
          <Tag size={22} />
        </div>
        <h3 className="min-w-0 font-display text-lg text-navy line-clamp-2">
          {offer.title}
        </h3>
      </div>

      <p className="text-xs font-medium text-muted line-clamp-1">
        {offer.category?.name}
        {offer.partner && ` · com ${offer.partner.companyName}`}
      </p>

      <p className="text-sm text-muted line-clamp-2 min-h-[2lh]">
        {offer.description}
      </p>

      <div className="mt-auto flex items-center justify-between gap-2 text-sm">
        {offer.endDate ? (
          <span className="text-xs text-muted">
            até {formatDate(offer.endDate)}
          </span>
        ) : (
          <span />
        )}
        <span className="inline-flex items-center gap-1 font-semibold text-navy transition-all group-hover:gap-2">
          Ver oferta <ArrowRight size={15} />
        </span>
      </div>
    </Link>
  );
}
