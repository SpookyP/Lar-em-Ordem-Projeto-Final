import { useState } from "react";
import {
  Combobox,
  ComboboxButton,
  ComboboxInput,
  ComboboxOptions,
} from "@headlessui/react";
import { ChevronDown } from "lucide-react";
import { FormField } from "./FormField";
import { SelectOption } from "./SelectOption";
import { cx, filterOptions, inputClass, optionsPanelClass } from "./styles";

/**
 * Select with a search box. options = [{ value, label, description? }].
 * value is the chosen option's value ("" when none); onChange receives the new value.
 */
export function SearchSelect({
  label,
  hint,
  error,
  required,
  className,
  options,
  value,
  onChange,
  placeholder = "Escolher...",
  emptyText = "Sem resultados.",
  disabled = false,
}) {
  const [query, setQuery] = useState("");
  const selected = options.find((o) => o.value === value) ?? null;
  const filtered = filterOptions(options, query);

  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <Combobox
        value={selected}
        by="value"
        onChange={(option) => onChange(option ? option.value : "")}
        onClose={() => setQuery("")}
        disabled={disabled}
        immediate
      >
        <div className="relative">
          <ComboboxInput
            className={cx(inputClass(Boolean(error)), "pr-10")}
            displayValue={(option) => option?.label ?? ""}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            aria-invalid={Boolean(error)}
            autoComplete="off"
          />
          <ComboboxButton className="absolute inset-y-0 right-0 flex items-center px-3 text-muted">
            <ChevronDown size={16} aria-hidden="true" />
          </ComboboxButton>
        </div>

        <ComboboxOptions anchor="bottom start" transition className={optionsPanelClass}>
          {filtered.length === 0 ? (
            <div className="px-3 py-2 text-sm text-muted">{emptyText}</div>
          ) : (
            filtered.map((option) => <SelectOption key={option.value} option={option} />)
          )}
        </ComboboxOptions>
      </Combobox>
    </FormField>
  );
}