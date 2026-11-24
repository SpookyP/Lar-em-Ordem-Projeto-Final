import { useState, useMemo } from "react";
import { Checkbox } from "../form";
import { EmptyState } from "../ui/EmptyState";
import { ChecklistItem } from "./ChecklistItem";

function getMissingRequiredText(count) {
  if (count === 0) return "todos os recomendados estão em ordem";
  if (count === 1) return "falta 1 recomendado";
  return `faltam ${count} recomendados`;
}

// the document types of the vault, ticked once attached, with a shortcut to attach the missing ones
export function DocumentChecklist({ items, summary, onAttach }) {
  const [onlyMissing, setOnlyMissing] = useState(false);

    const visibleItems = useMemo(() => {
        const base = onlyMissing
        ? items.filter((item) => !item.attached)
        : items;
        return [...base].sort(
        (a, b) =>
            Number(Boolean(b.recommended ?? b.required)) -
            Number(Boolean(a.recommended ?? a.required)),
        );
    }, [items, onlyMissing]);

  if (items.length === 0) return null;

  return (
    <section
      aria-labelledby="document-checklist-title"
      className="@container overflow-hidden rounded-2xl border border-border bg-card p-4 sm:p-6 min-w-0"
    >
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <h2
            id="document-checklist-title"
            className="text-lg font-semibold text-navy"
          >
            Checklist de documentos
          </h2>
          <p className="mt-1 text-sm text-muted">
            {summary.attached} de {summary.total} anexados ·{" "}
            {getMissingRequiredText(summary.missingRequired)}
          </p>
        </div>

        <div className="flex items-center gap-3 sm:w-72">
          <div
            role="progressbar"
            aria-label="Documentos anexados"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={summary.percent}
            className="h-2 flex-1 overflow-hidden rounded-full bg-border"
          >
            <div
              className="h-full rounded-full bg-teal transition-[width] duration-300"
              style={{ width: `${summary.percent}%` }}
            />
          </div>
          <span className="w-10 text-right text-sm font-semibold text-teal">
            {summary.percent}%
          </span>
        </div>
      </div>

      <div className="mt-4">
        <Checkbox
          checked={onlyMissing}
          onChange={setOnlyMissing}
          label="Mostrar apenas em falta"
        />
      </div>

      {visibleItems.length === 0 ? (
        <EmptyState className="mt-4" message="Não falta nenhum documento." />
      ) : (
        <ul className="mt-4 grid min-w-0 grid-cols-1 gap-3 @2xl:grid-cols-2">
          {visibleItems.map((item) => (
            <ChecklistItem key={item.id} item={item} onAttach={onAttach} />
          ))}
        </ul>
      )}
    </section>
  );
}