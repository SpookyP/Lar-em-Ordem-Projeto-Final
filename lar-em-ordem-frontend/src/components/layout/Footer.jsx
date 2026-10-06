export function Footer() {
  const year = new Date().getFullYear();

  const links = ["Sobre", "Ajuda", "Privacidade", "Termos"];

  return (
    <footer className="border-t border-navy/30 bg-navy/5 px-6 py-4">
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
        <span className="font-display text-navy">Lar em Ordem</span>

        <nav className="flex gap-5">
          {links.map((link) => (
            <a
              key={link}
              href="#"
              className="text-sm text-muted transition hover:text-navy"
            >
              {link}
            </a>
          ))}
        </nav>

        <span className="text-sm text-muted">© {year} Lar em Ordem</span>
      </div>
    </footer>
  );
}
