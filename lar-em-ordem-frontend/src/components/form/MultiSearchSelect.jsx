import { useMemo, useState } from "react";
import { Combobox, ComboboxInput, ComboboxOptions } from "@headlessui/react";
import { Search, X } from "lucide-react";
import { FormField } from "./FormField";
import { SelectOption } from "./SelectOption";
import { chipClass, cx, filterOptions, inputClass, optionsPanelClass } from "./styles";

/**
 * Multi-choice select with search; the chosen options show as removable chips.
 * value is an array of option values; onChange receives the new array.
 * chipLabel(option) lets the caller change the chip text.
 */
export function MultiSearchSelect({
  label,
  hint,
  error,
  required,
  className,
  options,
  value,
  onChange,
  placeholder = "Pesquisar...",
  emptyText = "Sem resultados.",
  chipLabel,
}) {
  const [query, setQuery] = useState("");
  const selectedSet = useMemo(() => new Set(value), [value]);
  const selected = useMemo(
    () => options.filter((o) => selectedSet.has(o.value)),
    [options, selectedSet],
  );
  const filtered = filterOptions(options, query);

  function remove(optionValue) {
    onChange(value.filter((v) => v !== optionValue));
  }

  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <Combobox
        multiple
        value={selected}
        by="value"
        onChange={(list) => onChange(list.map((o) => o.value))}
        onClose={() => setQuery("")}
        immediate
      >
        <div className="relative">
          <Search
            size={16}
            aria-hidden="true"
            className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"
          />
          <ComboboxInput
            className={cx(inputClass(Boolean(error)), "pl-10")}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            aria-invalid={Boolean(error)}
            autoComplete="off"
          />
        </div>

        <ComboboxOptions anchor="bottom start" transition className={optionsPanelClass}>
          {filtered.length === 0 ? (
            <div className="px-3 py-2 text-sm text-muted">{emptyText}</div>
          ) : (
            filtered.map((option) => <SelectOption key={option.value} option={option} />)
          )}
        </ComboboxOptions>
      </Combobox>

      {selected.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 pt-1">
          {selected.map((o) => (
            <span key={o.value} className={chipClass}>
              {chipLabel ? chipLabel(o) : o.label}
              <button
                type="button"
                onClick={() => remove(o.value)}
                aria-label={`Remover ${o.label}`}
                className="rounded-full p-0.5 transition hover:bg-navy/20"
              >
                <X size={12} aria-hidden="true" />
              </button>
            </span>
          ))}
          <button
            type="button"
            onClick={() => onChange([])}
            className="text-xs text-muted underline transition hover:text-navy"
          >
            Limpar tudo
          </button>
        </div>
      )}
    </FormField>
  );
}