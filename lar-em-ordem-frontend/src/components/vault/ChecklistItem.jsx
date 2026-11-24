import { Check, Plus } from "lucide-react";

export function ChecklistItem({ item, onAttach }) {
  const { icon: Icon, name, group, required, attached } = item;

  return (
    <li
      className={`flex min-w-0 items-center gap-2.5 rounded-xl border p-3 transition sm:gap-3 ${
        attached ? "border-teal/20 bg-teal/5" : "border-border bg-card"
      }`}
    >
      <span
        aria-hidden="true"
        className={`flex size-5 shrink-0 items-center justify-center rounded-md border ${
          attached ? "border-teal bg-teal text-white" : "border-border bg-card"
        }`}
      >
        {attached && <Check size={14} strokeWidth={3} />}
      </span>

      {Icon && (
        <Icon size={20} aria-hidden="true" className="shrink-0 text-muted" />
      )}

      <div className="min-w-0 flex-1">
        <p
          title={name}
          className={`truncate text-sm font-semibold ${
            attached ? "text-teal" : "text-navy"
          }`}
        >
          {name}
        </p>
        <p className="truncate text-xs text-muted">
          {group}
          {required && (
            <>
              {" · "}
              <span className="font-semibold">Recomendado</span>
            </>
          )}
        </p>
      </div>

      {attached ? (
        <span className="shrink-0 text-xs font-semibold text-teal sm:text-sm">
          Anexado
        </span>
      ) : (
        <button
          type="button"
          onClick={() => onAttach(item)}
          aria-label={`Anexar ${name}`}
          className="inline-flex shrink-0 items-center gap-1 rounded-full border border-navy px-3 py-1.5 text-xs font-semibold text-navy outline-none transition hover:bg-navy hover:text-white focus-visible:ring-4 focus-visible:ring-navy/10 sm:gap-1.5 sm:px-4 sm:py-2 sm:text-sm"
        >
          <Plus size={15} aria-hidden="true" className="sm:size-4" />
          Anexar
        </button>
      )}
    </li>
  );
}