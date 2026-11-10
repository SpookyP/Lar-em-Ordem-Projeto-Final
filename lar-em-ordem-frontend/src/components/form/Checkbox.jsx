import { Checkbox as HeadlessCheckbox, Description, Field, Label } from "@headlessui/react";
import { Check } from "lucide-react";
import { cx } from "./styles";

/**
 * A checkbox with its label (which may contain links) and an error or a hint under it.
 * Headless UI links the label and the messages to the box for screen readers.
 */
export function Checkbox({ checked, onChange, label, hint, error, required = false }) {
  return (
    <Field className="flex flex-col gap-1.5">
      <div className="flex items-start gap-3">
        <HeadlessCheckbox
          checked={checked}
          onChange={onChange}
          aria-required={required || undefined}
          aria-invalid={error ? true : undefined}
          className={cx(
            "group mt-0.5 flex size-5 shrink-0 cursor-pointer items-center justify-center rounded-md border bg-card outline-none transition focus-visible:ring-4",
            error ? "border-red focus-visible:ring-red/10" : "border-border focus-visible:ring-navy/10",
            "data-checked:border-navy data-checked:bg-navy",
          )}
        >
          <Check
            size={14}
            strokeWidth={3}
            aria-hidden="true"
            className="text-white opacity-0 group-data-checked:opacity-100"
          />
        </HeadlessCheckbox>
        <Label className="cursor-pointer text-sm text-text">
          {label}
          {required && (
            <span className="ml-0.5 text-red" aria-hidden="true">
              *
            </span>
          )}
        </Label>
      </div>
      {error ? (
        <Description className="pl-8 text-xs text-red" role="alert">
          {error}
        </Description>
      ) : hint ? (
        <Description className="pl-8 text-xs text-muted">{hint}</Description>
      ) : null}
    </Field>
  );
}