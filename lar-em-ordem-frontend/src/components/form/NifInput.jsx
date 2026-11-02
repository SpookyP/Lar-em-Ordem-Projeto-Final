import { TextInput } from "./TextInput";

/** NIF: digits only, at most 9. */
export function NifInput({ onChange, ...props }) {
  return (
    <TextInput
      inputMode="numeric"
      autoComplete="off"
      placeholder="123456789"
      maxLength={9}
      onChange={(value) => onChange(value.replace(/\D/g, "").slice(0, 9))}
      {...props}
    />
  );
}