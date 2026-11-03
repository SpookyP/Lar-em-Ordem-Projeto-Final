import { PasswordInput } from "../ui/PasswordInput";
import { FormField } from "./FormField";
import { inputClass } from "./styles";

// label + error around the existing PasswordInput (which already has show/hide)
export function PasswordField({
  label,
  hint,
  error,
  required,
  className,
  value,
  onChange,
  ...props
}) {
  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <PasswordInput
        value={value ?? ""}
        onChange={(e) => onChange(e.target.value)}
        invalid={Boolean(error)}
        spellCheck={false}
        className={inputClass(Boolean(error))}
        {...props}
      />
    </FormField>
  );
}