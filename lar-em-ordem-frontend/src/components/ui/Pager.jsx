import { ChevronLeft, ChevronRight } from "lucide-react";

export function Pager({ page, totalPages, onChange }) {
  if (totalPages <= 1) return null;

  return (
    <div className="flex items-center justify-between pt-3 border-t border-border/60 text-xs text-muted">
      <span>
        Página <strong className="text-navy">{page}</strong> de{" "}
        <strong className="text-navy">{totalPages}</strong>
      </span>
      <div className="flex items-center gap-1.5">
        <button
          onClick={() => onChange(page - 1)}
          disabled={page === 1}
          aria-label="Página anterior"
          className="p-1.5 rounded-lg border border-border bg-surface hover:bg-border/50 disabled:opacity-40 disabled:cursor-not-allowed transition text-navy cursor-pointer"
        >
          <ChevronLeft size={14} />
        </button>
        <button
          onClick={() => onChange(page + 1)}
          disabled={page === totalPages}
          aria-label="Página seguinte"
          className="p-1.5 rounded-lg border border-border bg-surface hover:bg-border/50 disabled:opacity-40 disabled:cursor-not-allowed transition text-navy cursor-pointer"
        >
          <ChevronRight size={14} />
        </button>
      </div>
    </div>
  );
}