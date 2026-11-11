import { Link, Outlet } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { AppLayout } from "./AppLayout";
import { Footer } from "./Footer";

/**
 * Layout for pages anyone can open (privacy policy, later the public landing page):
 * signed-in users get the app's sidebar, visitors a simple header with sign-in and sign-up.
 */
export function SiteLayout() {
  const { isAuthenticated, loading } = useAuth();

  if (loading) return null;
  if (isAuthenticated) return <AppLayout />;

  return (
    <div className="flex min-h-screen flex-col bg-surface">
      <header className="flex items-center justify-between gap-4 border-b border-navy/10 bg-card px-6 py-4">
        <Link to="/login" className="font-display text-xl text-navy">
          Lar em Ordem
        </Link>
        <nav className="flex items-center gap-3 text-sm">
          <Link to="/login" className="font-medium text-muted transition hover:text-navy">
            Entrar
          </Link>
          <Link
            to="/register"
            className="rounded-xl bg-navy px-4 py-2 font-medium text-white transition hover:bg-navy-dark"
          >
            Criar conta
          </Link>
        </nav>
      </header>
      <main className="flex-1 p-6">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}