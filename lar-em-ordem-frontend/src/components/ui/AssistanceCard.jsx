import { Link } from "react-router-dom";
import { Clock, Wrench } from "lucide-react";

export function AssistanceCard({
  icon: Icon = Wrench,
  iconColor = "bg-orange-light text-orange",
  id,
  category,
  description,
  postedAt,
  status,
  to,
}) {
  return (
    <div className="flex flex-col sm:flex-row rounded-xl bg-surface border border-border/50 hover:border-orange/40 transition-colors overflow-hidden">
      {/* Corpo do ticket */}
      <div className="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
        <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${iconColor}`}>
          <Icon size={16} />
        </div>

        <div className="flex min-w-0 flex-col">
          <div className="flex items-center gap-2">
            <h3 className="truncate font-display text-sm text-navy capitalize">
              {category}
            </h3>
          </div>
          <p className="text-xs text-muted truncate max-w-xs sm:max-w-md">
            {description}
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

      {/* Talão totalmente responsivo utilizando classes padrão do Tailwind */}
      <div className="flex items-center justify-between gap-4 border-t sm:border-t-0 sm:border-l border-dashed border-navy/20 px-4 py-3 sm:w-64 shrink-0">
        <span className="font-medium text-[11px] capitalize bg-orange-light text-orange px-2.5 py-1 rounded-md whitespace-nowrap">
          {status || "Pendente"}
        </span>
        {to && (
          <Link
            to={to}
            className="rounded-full bg-navy px-3.5 py-1.5 text-xs whitespace-nowrap text-white transition hover:opacity-90 text-center"
          >
            Ver detalhes
          </Link>
        )}
      </div>
    </div>
  );
}