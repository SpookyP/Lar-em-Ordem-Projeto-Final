import { Link } from "react-router-dom";
import { formatDate } from "../../utils/date";
import { ArrowRight } from "lucide-react";

const THEMES = ["bg-orange", "bg-navy", "bg-teal", "bg-navy-dark"];

export function PartnerOfferCard({ offer, index = 0 }) {
  const theme = THEMES[index % THEMES.length];

  return (
    <Link
      to={`/offers/${offer.id}`}
      className={`relative block h-full overflow-hidden rounded-2xl p-5 text-white shadow-sm transition hover:opacity-95 ${theme}`}
    >
      <span
        aria-hidden="true"
        className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"
      />

      <div className="relative flex h-full flex-col gap-2">
        <span className="inline-flex w-fit items-center rounded-full bg-white/15 px-2.5 py-0.5 text-[11px] font-medium capitalize">
          {offer.type}
        </span>

        <h3 className="font-display text-xl leading-tight line-clamp-2 min-h-[2lh] wrap-break-word">
          {offer.title}
        </h3>

        {offer.partner && (
          <p className="text-sm text-white/80 line-clamp-1 wrap-break-word">
            com {offer.partner.companyName}
          </p>
        )}

        <p className="text-sm text-white/90 line-clamp-2 min-h-[2lh] wrap-break-word">
          {offer.description}
        </p>

        {offer.endDate && (
          <p className="text-xs text-white/70">
            até {formatDate(offer.endDate)}
          </p>
        )}

        <span className="mt-auto self-end inline-flex items-center gap-1 rounded-full bg-white px-4 py-2 text-sm font-semibold text-navy">
          Ver oferta <ArrowRight size={16} />
        </span>
      </div>
    </Link>
  );
}
