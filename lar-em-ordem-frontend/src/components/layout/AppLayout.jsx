import { Outlet } from "react-router-dom";
import { SideBar } from "./Sidebar";
import { MobileNav } from "./MobileNav";
import { Footer } from "./Footer";

export function AppLayout() {
  return (
    <div className="min-h-screen bg-surface">
      <SideBar />
      <div className="flex min-h-screen flex-col md:ml-60">
        <MobileNav />
        <main className="flex-1 p-6">
          <Outlet />
        </main>
        <Footer />
      </div>
    </div>
  );
}
