import { Link } from "react-router-dom";
import { Clock, Wrench } from "lucide-react";
import { statusInfo } from "../../data/assistanceInfo";

export function AssistanceCard({
  icon: Icon = Wrench,
  iconColor = "bg-orange-light text-orange",
  id,
  category,
  title,
  postedAt,
  status,
  proposals = 0,
  to,
}) {
  const badge = statusInfo(status);

  return (
    <div className="flex flex-col sm:flex-row rounded-xl bg-surface border border-border/50 hover:border-orange/40 transition-colors overflow-hidden">
      <div className="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
        <div
          className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${iconColor}`}
        >
          <Icon size={16} />
        </div>

        <div className="flex min-w-0 flex-1 flex-col">
          <div className="flex items-center gap-2">
            <h3 className="truncate font-display text-sm text-navy capitalize">
              {category}
            </h3>
            <span
              className={`ml-auto shrink-0 rounded-md px-2.5 py-1 text-[11px] font-medium whitespace-nowrap ${badge.style}`}
            >
              {badge.label}
            </span>
          </div>
          <p className="mt-2 text-xs text-muted truncate max-w-xs sm:max-w-md">
            {title}
          </p>
          <div className="flex flex-wrap items-center gap-x-3 text-[11px] text-muted mt-0.5">
            <span>#{id}</span>
            <span className="flex items-center gap-1">
              <Clock size={11} />
              {postedAt}
            </span>
          </div>
        </div>
      </div>

      <div className="flex shrink-0 flex-col items-center justify-center gap-1 border-t border-dashed border-navy/20 px-4 py-3 sm:border-l sm:border-t-0">
        {proposals > 0 && (
          <span className="text-[11px] font-medium text-navy text-center">
            {proposals} {proposals === 1 ? "proposta nova" : "propostas novas"}
          </span>
        )}
        {to && (
          <Link
            to={to}
            className="rounded-full bg-navy px-3 py-1 text-[11px] whitespace-nowrap text-white transition hover:opacity-90 text-center"
          >
            Ver Detalhes
          </Link>
        )}
      </div>
    </div>
  );
}
