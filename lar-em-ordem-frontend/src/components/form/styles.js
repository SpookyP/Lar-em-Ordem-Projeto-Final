// Shared class names and helpers for the form components.
import { slugify } from "../../data/assistanceInfo";

export function cx(...classes) {
  return classes.filter(Boolean).join(" ");
}

const inputBase =
  "w-full rounded-xl border bg-card px-3.5 py-2.5 text-sm text-text placeholder:text-muted/70 outline-none transition focus:ring-4 disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted";

// border and focus ring change colour when the field has an error
export function inputClass(invalid = false) {
  return cx(
    inputBase,
    invalid
      ? "border-red focus:ring-red/10"
      : "border-border focus:border-navy focus:ring-navy/10",
  );
}

export const chipClass =
  "inline-flex items-center gap-1 rounded-full bg-navy/10 px-3 py-1 text-xs font-medium text-navy";

export const optionsPanelClass =
  "z-50 max-h-64 w-(--input-width) overflow-y-auto rounded-xl border border-border bg-card p-1 shadow-lg [--anchor-gap:6px] transition duration-100 ease-out data-closed:opacity-0";

// slugify strips accents and lower-cases, so "agueda" finds "Águeda"
export function filterOptions(options, query) {
  const q = slugify(query.trim());
  if (!q) return options;
  const matches = (text) => slugify(String(text ?? "")).includes(q);
  return options.filter((o) => matches(o.label) || matches(o.description));
}