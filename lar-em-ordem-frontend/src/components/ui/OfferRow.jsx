import { Link, useLocation } from "react-router-dom";
import { Tag, ChevronRight } from "lucide-react";
import { formatDate } from "../../utils/date";

const COLORS = ["bg-navy", "bg-orange", "bg-teal", "bg-navy-dark", "bg-red"];

export function OfferRow({ offer, index = 0 }) {
  const location = useLocation();
  const color = COLORS[index % COLORS.length];

  return (
    <Link
      to={`/offers/${offer.id}`}
      state={{ background: location }}
      className="flex items-center gap-4 p-4 transition hover:bg-surface"
    >
      <div
        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-white ${color}`}
      >
        <Tag size={18} />
      </div>

      <div className="min-w-0 flex-1">
        <h3 className="truncate font-medium text-navy">{offer.title}</h3>
        <p className="truncate text-sm text-muted">
          {offer.category?.name}
          {offer.partner && ` · com ${offer.partner.companyName}`}
          {offer.description && ` — ${offer.description}`}
        </p>
      </div>

      <div className="flex shrink-0 items-center gap-3 text-sm text-muted">
        {offer.endDate && (
          <span className="hidden sm:inline">
            até {formatDate(offer.endDate)}
          </span>
        )}
        <ChevronRight size={18} />
      </div>
    </Link>
  );
}
