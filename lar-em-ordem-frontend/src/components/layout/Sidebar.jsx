import { NavLink } from "react-router-dom";
import { navItems } from "./navItems";

export function SideBar() {
    return (
        <aside className="hidden md:flex w-60 flex-col bg-navy text-white p-4 min-h-screen">
            <div className="font-display text-xl mb-8 px-2"> Lar em Ordem</div>

            <nav className="flex flex-col gap-1">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    return (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            className={({isActive}) =>
                                `flex items-center gap-3 rounded-lg pz-3 py-2 text-sm transition 
                                ${isActive ? 'bg-navy-light text-white' : 'text-white/70 hover:bg-navy-light hover:text-white'}`
                            }>
                            <Icon size={20} />
                            {item.label}
                        </NavLink>
                    );
                })}
            </nav>

            
        </aside>
    );
}