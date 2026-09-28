import { Outlet } from 'react-router-dom';
import { SideBar } from './Sidebar';
import { BottomNav } from './BottomNav';

export function AppLayout(){
    return (
        <div className="main-h-screen flex bg-surface">
            <SideBar />
            <main className="flex-1 p-6 pb-20 md:pb-6">
                <Outlet />
            </main>
            <BottomNav />
        </div>
    )
}