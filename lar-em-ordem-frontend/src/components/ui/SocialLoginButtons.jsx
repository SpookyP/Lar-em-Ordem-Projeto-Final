const API_URL = String(import.meta.env.VITE_API_BASE_URL ?? "").replace(
  /\/$/,
  "",
);

const linkClass =
  "flex items-center justify-center gap-3 rounded-xl border border-border px-5 py-3 font-medium text-text transition hover:bg-surface";

function MicrosoftIcon() {
  return (
    <svg viewBox="0 0 21 21" className="h-5 w-5" aria-hidden="true">
      <rect x="1" y="1" width="9" height="9" fill="#F25022" />
      <rect x="11" y="1" width="9" height="9" fill="#7FBA00" />
      <rect x="1" y="11" width="9" height="9" fill="#00A4EF" />
      <rect x="11" y="11" width="9" height="9" fill="#FFB900" />
    </svg>
  );
}

export function SocialLoginButtons() {
  return (
    <div className="mb-6 flex flex-col gap-3">
      <a href={`${API_URL}/auth/google/redirect`} className={linkClass}>
        <span className="w-5 text-center text-lg font-bold text-[#4285F4]">
          G
        </span>
        Continuar com Google
      </a>
      <a href={`${API_URL}/auth/microsoft/redirect`} className={linkClass}>
        <MicrosoftIcon />
        Continuar com Microsoft
      </a>

      <div className="mt-2 flex items-center gap-3 text-xs text-muted">
        <span className="h-px flex-1 bg-border" />
        ou
        <span className="h-px flex-1 bg-border" />
      </div>
    </div>
  );
}