import { Input } from "@headlessui/react";
import { FormField } from "./FormField";
import { cx, inputClass } from "./styles";

/** Text input with an optional lucide icon. onChange receives the new value, not the event. */
export function TextInput({
  label,
  hint,
  error,
  required,
  icon: Icon,
  className,
  value,
  onChange,
  ...props
}) {
  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <div className="relative">
        {Icon && (
          <Icon
            size={16}
            aria-hidden="true"
            className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"
          />
        )}
        <Input
          value={value ?? ""}
          onChange={(e) => onChange(e.target.value)}
          invalid={Boolean(error)}
          className={cx(inputClass(Boolean(error)), Icon && "pl-10")}
          {...props}
        />
      </div>
    </FormField>
  );
}