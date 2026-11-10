import { Link } from "react-router-dom";

const LINKS = [
  { label: "Sobre", to: null },
  { label: "Ajuda", to: null },
  { label: "Privacidade", to: "/privacy" },
  { label: "Termos", to: null },
];

export function Footer() {
  const year = new Date().getFullYear();
  return (
    <footer className="border-t border-navy/30 bg-navy/5 px-6 py-4">
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
        <span className="font-display text-navy">Lar em Ordem</span>

        <nav className="flex gap-5">
          {LINKS.map(({ label, to }) =>
            to ? (
              <Link key={label} to={to} className="text-sm text-muted transition hover:text-navy">
                {label}
              </Link>
            ) : (
              <a key={label} href="#" className="text-sm text-muted transition hover:text-navy">
                {label}
              </a>
            ),
          )}
        </nav>

        <span className="text-sm text-muted">© {year} Lar em Ordem</span>
      </div>
    </footer>
  );
}
