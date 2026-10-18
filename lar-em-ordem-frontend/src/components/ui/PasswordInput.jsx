import { useState } from "react";
import { Eye, EyeOff } from "lucide-react";

export function PasswordInput({ className = "", ...props }) {
  const [show, setShow] = useState(false);
  return (
    <div className="relative">
      <input
        type={show ? "text" : "password"}
        className={`w-full pr-10 ${className}`}
        {...props}
      />
      <button
        type="button"
        onClick={() => setShow((s) => !s)}
        aria-label={show ? "Esconder password" : "Mostrar password"}
        className="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-navy"
      >
        {show ? <Eye size={18} /> : <EyeOff size={18} />}
      </button>
    </div>
  );
}
