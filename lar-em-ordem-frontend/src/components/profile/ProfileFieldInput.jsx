import { Building2, Globe, Mail } from "lucide-react";
import { ChoiceCards, NifInput, PhoneInput, TextArea, TextInput } from "../form";

const DESCRIPTION_MAX = 2000;

// picks the right input for a field from data/registerFields.js
export function ProfileFieldInput({ field, value, onChange, error, className }) {
  const common = {
    label: field.label,
    hint: field.hint,
    required: !field.optional,
    value,
    onChange,
    error,
    className,
  };

    if (field.type === "choice") {
    return (
      <div className={`flex flex-col gap-1.5 ${className ?? ""}`}>
        <span className="text-sm font-medium text-text">
          {field.label}
          <span className="ml-0.5 text-red" aria-hidden="true">
            *
          </span>
        </span>
        <ChoiceCards
          label={field.label}
          options={field.options}
          value={value}
          onChange={onChange}
          error={error}
        />
      </div>
    );
  }
  if (field.name === "nif") return <NifInput {...common} />;
  if (field.type === "tel") return <PhoneInput {...common} />;
  if (field.type === "textarea") return <TextArea {...common} maxLength={DESCRIPTION_MAX} />;
  if (field.type === "email") {
    return (
      <TextInput
        {...common}
        type="email"
        icon={Mail}
        autoComplete="email"
        autoCapitalize="none"
        spellCheck={false}
        maxLength={255}
      />
    );
  }
  if (field.type === "url") {
    return (
      <TextInput
        {...common}
        type="url"
        icon={Globe}
        placeholder="https://"
        autoCapitalize="none"
        spellCheck={false}
        maxLength={255}
      />
    );
  }

  return (
    <TextInput
      {...common}
      icon={field.name === "company_name" ? Building2 : undefined}
      maxLength={255}
    />
  );
}