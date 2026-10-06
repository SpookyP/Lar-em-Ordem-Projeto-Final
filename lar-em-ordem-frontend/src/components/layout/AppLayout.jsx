import { Outlet } from "react-router-dom";
import { SideBar } from "./Sidebar";
import { BottomNav } from "./BottomNav";
import { Footer } from "./Footer";

export function AppLayout() {
  return (
    <div className="min-h-screen flex bg-surface">
      <SideBar />
      <div className="flex-1 flex flex-col min-h-screen">
        <main className="flex-1 p-6 pb-20 md:pb-6">
          <Outlet />
        </main>
        <Footer />
      </div>
      <BottomNav />
    </div>
  );
}
