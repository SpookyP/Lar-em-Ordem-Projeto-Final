import { NavLink } from "react-router-dom";
import { navItems } from "./navItems";

export function BottomNav() {
    return (
        <nav className="md:hidden fixed bottom-0 left-0 right-0 flex justify-around border-t border-border bg-card">
            {navItems.map((item) => {
                const Icon = item.icon;
                return (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className={({isActive}) =>
                            `flex flex-col items-center gap-1 py-2 px-3 text-xs transition 
                            ${isActive ? 'text-navy' : 'text-muted'}`
                        }>
                        <Icon size={20} />
                        {item.label}
                    </NavLink>
                );
            })}
        </nav>
    );
}