import { Check, X } from "lucide-react";
import { checkPassword } from "../../utils/passwordValidation";

export function PasswordRequirements({ password }) {
  const results = checkPassword(password);

  return (
    <ul className="flex flex-col gap-1 text-sm">
      {results.map((rule) => (
        <li
          key={rule.id}
          className={`flex items-center gap-2 ${rule.ok ? "text-green-600" : "text-muted"}`}
        >
          {rule.ok ? <Check size={16} /> : <X size={16} />}
          {rule.label}
        </li>
      ))}
    </ul>
  );
}
