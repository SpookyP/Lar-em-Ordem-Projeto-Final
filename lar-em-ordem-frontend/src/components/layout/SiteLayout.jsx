import { Link, Outlet } from "react-router-dom";
import { House } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { AppLayout } from "./AppLayout";
import { Footer } from "./Footer";

/**
 * Layout for pages anyone can open (login, sign-up, privacy policy):
 * signed-in users get the app's sidebar, visitors a simple header with sign-in and sign-up.
 * With "flush" the page fills the whole area (login and sign-up bring their own columns).
 */
export function SiteLayout({ flush = false }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) return null;
  if (isAuthenticated) return <AppLayout />;

  return (
    <div className={`flex flex-col ${flush ? "h-dvh bg-card" : "min-h-screen bg-surface"}`}>
      <header className="sticky top-0 z-40 flex items-center justify-between gap-4 border-b border-border bg-card px-6 py-3 lg:px-10">
        <Link to="/login" className="flex items-center gap-2.5">
          <span className="flex size-9 items-center justify-center rounded-xl bg-navy text-white">
            <House size={18} aria-hidden="true" />
          </span>
          <span className="font-display text-xl text-navy">Lar em Ordem</span>
        </Link>
        <nav className="flex items-center gap-2 text-sm">
          <Link
            to="/login"
            className="rounded-full bg-surface px-4 py-2 font-medium text-navy transition hover:bg-border"
          >
            Entrar
          </Link>
          <Link
            to="/register"
            className="rounded-full bg-navy px-4 py-2 font-semibold text-white transition hover:bg-navy-light"
          >
            Criar conta
          </Link>
        </nav>
      </header>
      <main className={flush ? "flex min-h-0 flex-1 flex-col" : "flex-1 p-6"}>
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}