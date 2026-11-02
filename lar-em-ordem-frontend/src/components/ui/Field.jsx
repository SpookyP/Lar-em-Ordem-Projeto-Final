import { PasswordInput } from "./PasswordInput";

export function Field({
  label,
  name,
  value,
  onChange,
  type = "text",
  disabled = false,
}) {
  const shared =
    "rounded-lg border border-border px-3 py-2 text-text outline-none focus:border-navy disabled:bg-surface disabled:text-muted disabled:cursor-default";

  return (
    <label className="flex flex-col gap-1">
      <span className="text-sm text-text">{label}</span>
      {type === "textarea" ? (
        <textarea
          name={name}
          value={value}
          onChange={onChange}
          rows={3}
          disabled={disabled}
          className={shared}
        />
      ) : type === "password" ? (
        <PasswordInput
          name={name}
          value={value}
          onChange={onChange}
          className={shared}
        />
      ) : (
        <input
          name={name}
          type={type}
          value={value}
          onChange={onChange}
          disabled={disabled}
          className={shared}
        />
      )}
    </label>
  );
}
