import { Textarea } from "@headlessui/react";
import { FormField } from "./FormField";
import { cx, inputClass } from "./styles";

/** Multi-line input; with maxLength it shows a "120/2000" counter. */
export function TextArea({
  label,
  hint,
  error,
  required,
  className,
  value,
  onChange,
  maxLength,
  rows = 4,
  ...props
}) {
  const length = (value ?? "").length;

  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <Textarea
        value={value ?? ""}
        onChange={(e) => onChange(e.target.value)}
        rows={rows}
        maxLength={maxLength}
        invalid={Boolean(error)}
        className={cx(inputClass(Boolean(error)), "resize-y")}
        {...props}
      />
      {maxLength && (
        <span className="self-end text-xs text-muted" aria-hidden="true">
          {length}/{maxLength}
        </span>
      )}
    </FormField>
  );
}