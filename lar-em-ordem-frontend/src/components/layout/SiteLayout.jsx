import { Link, Outlet } from "react-router-dom";
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
    <div
      className={`flex flex-col ${flush ? "min-h-dvh bg-card lg:h-dvh" : "min-h-screen bg-surface"}`}
    >
      <header className="sticky top-0 z-40 flex items-center justify-between gap-3 border-b border-border bg-card px-4 py-3 sm:gap-4 sm:px-6 lg:px-10">
        <Link to="/login" className="flex items-center gap-2.5">
          <img src="/logo.svg" alt="" className="size-9" />
          <span className="whitespace-nowrap font-display text-base text-navy sm:text-xl">
            Lar em Ordem
          </span>
        </Link>
        <nav className="flex items-center gap-1.5 text-xs sm:gap-2 sm:text-sm">
          <Link
            to="/login"
            className="whitespace-nowrap rounded-full bg-surface px-2.5 py-1.5 font-medium text-navy transition hover:bg-border sm:px-4 sm:py-2"
          >
            Entrar
          </Link>
          <Link
            to="/register"
            className="whitespace-nowrap rounded-full bg-navy px-2.5 py-1.5 font-semibold text-white transition hover:bg-navy-light sm:px-4 sm:py-2"
          >
            Criar conta
          </Link>
        </nav>
      </header>
      <main
        className={flush ? "flex flex-1 flex-col lg:min-h-0" : "flex-1 p-6"}
      >
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
