import { Link } from "react-router-dom";
import { MapPin, Clock, Users } from "lucide-react";

function offersLabel(offers) {
  if (offers === 0) return "Sem ofertas";
  return `${offers} ${offers === 1 ? "oferta" : "ofertas"}`;
}

export function OfferCard({
  icon: Icon,
  iconColor,
  id,
  title,
  description,
  location,
  postedAt,
  budget,
  urgent,
  offers,
  to,
}) {
  return (
    <div className="flex flex-col sm:flex-row rounded-xl bg-surface">
      {/* corpo do ticket */}
      <div className="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
        <div
          className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${iconColor}`}
        >
          <Icon size={16} />
        </div>

        <div className="flex min-w-0 flex-col">
          <div className="flex items-center gap-2">
            <h3 className="truncate font-display text-base text-navy">
              {title}
            </h3>
          </div>
          <div className="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted">
            <span>#{id}</span>
            <span className="flex items-center gap-1">
              <MapPin size={12} />
              {location}
            </span>
            <span className="flex items-center gap-1">
              <Clock size={12} />
              {postedAt}
            </span>
            <span className="flex items-center gap-1">
              <Users size={12} />
              {offersLabel(offers)}
            </span>
          </div>
        </div>
      </div>

      {/* talao (parte destacavel do ticket) */}
      <div className="flex items-center justify-between gap-4 border-t sm:border-t-0 sm:border-l border-dashed border-navy/20 px-4 py-3 sm:w-64">
        <div className="font-display text-base whitespace-nowrap text-navy">{budget}</div>
        <Link
          to={to}
          className="rounded-full bg-navy px-3 py-1.5 text-sm whitespace-nowrap text-white transition hover:opacity-90"
        >
          Fazer oferta
        </Link>
      </div>
    </div>
  );
}
