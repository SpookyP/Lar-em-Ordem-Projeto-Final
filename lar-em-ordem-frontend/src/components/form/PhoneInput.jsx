import { Phone } from "lucide-react";
import { TextInput } from "./TextInput";
import { cleanPhone, formatPhone } from "../../utils/format";

/** Phone number: shown grouped ("912 345 678"), stored without spaces. */
export function PhoneInput({ value, onChange, ...props }) {
  return (
    <TextInput
      type="tel"
      inputMode="tel"
      autoComplete="tel"
      icon={Phone}
      placeholder="912 345 678"
      value={formatPhone(value)}
      onChange={(text) => onChange(cleanPhone(text))}
      {...props}
    />
  );
}