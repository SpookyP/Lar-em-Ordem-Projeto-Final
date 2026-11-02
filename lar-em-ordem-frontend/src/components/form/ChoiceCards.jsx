import { Radio, RadioGroup } from "@headlessui/react";
import { Check } from "lucide-react";
import { cx } from "./styles";

// radio group shown as cards; options = [{ value, label, description?, icon? }]
export function ChoiceCards({ label, options, value, onChange, error }) {
  return (
    <div className="flex flex-col gap-1.5">
      <RadioGroup
        value={value}
        onChange={onChange}
        aria-label={label}
        className="flex flex-col gap-3"
      >
        {options.map((option) => {
          const Icon = option.icon;

          return (
            <Radio
              key={option.value}
              value={option.value}
              className={cx(
                "group flex cursor-pointer items-center gap-4 rounded-xl border p-4 outline-none transition",
                "data-checked:border-navy data-checked:bg-navy/5 data-focus:ring-4 data-focus:ring-navy/10",
                error ? "border-red" : "border-border hover:border-navy/40",
              )}
            >
              {Icon && (
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface text-navy transition group-data-checked:bg-navy group-data-checked:text-white">
                  <Icon size={20} aria-hidden="true" />
                </span>
              )}
              <span className="min-w-0 flex-1">
                <span className="block font-medium text-text">{option.label}</span>
                {option.description && (
                  <span className="block text-sm text-muted">{option.description}</span>
                )}
              </span>
              <Check
                size={18}
                aria-hidden="true"
                className="shrink-0 text-navy opacity-0 transition group-data-checked:opacity-100"
              />
            </Radio>
          );
        })}
      </RadioGroup>
      {error && (
        <p role="alert" className="text-xs text-red">
          {error}
        </p>
      )}
    </div>
  );
}