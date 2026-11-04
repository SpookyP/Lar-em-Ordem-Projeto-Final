import { ComboboxOption } from "@headlessui/react";
import { Check } from "lucide-react";

/** One row in a SearchSelect / MultiSearchSelect list. */
export function SelectOption({ option }) {
  return (
    <ComboboxOption
      value={option}
      className="group flex cursor-pointer select-none items-center gap-2 rounded-lg px-3 py-2 text-sm text-text data-focus:bg-surface"
    >
      <Check
        size={16}
        aria-hidden="true"
        className="invisible shrink-0 text-navy group-data-selected:visible"
      />
      <span className="flex-1 truncate">{option.label}</span>
      {option.description && (
        <span className="shrink-0 text-xs text-muted">{option.description}</span>
      )}
    </ComboboxOption>
  );
}