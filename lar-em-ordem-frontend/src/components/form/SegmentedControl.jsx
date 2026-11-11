import { Radio, RadioGroup } from "@headlessui/react";
import { FormField } from "./FormField";
import { cx } from "./styles";

/**
 * A few short choices side by side, as pills. options = [{ value, label, description?, activeClass? }].
 * The chosen option's description shows as the hint; activeClass colours the chosen pill.
 */
export function SegmentedControl({ label, options, value, onChange, error, hint, required, className }) {
  const selected = options.find((option) => option.value === value);

  return (
    <FormField
      label={label}
      hint={selected?.description ?? hint}
      error={error}
      required={required}
      className={className}
    >
      <RadioGroup
        value={value}
        onChange={onChange}
        aria-label={label}
        className={cx(
          "flex gap-1 rounded-xl border bg-surface p-1",
          error ? "border-red" : "border-border",
        )}
      >
        {options.map((option) => (
          <Radio
            key={option.value}
            value={option.value}
            className={cx(
              "flex flex-1 cursor-pointer items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium text-muted outline-none transition",
              "hover:text-navy data-focus:ring-4 data-focus:ring-navy/10",
              "data-checked:shadow-sm",
              option.activeClass ?? "data-checked:bg-card data-checked:text-navy",
            )}
          >
            {option.label}
          </Radio>
        ))}
      </RadioGroup>
    </FormField>
  );
}