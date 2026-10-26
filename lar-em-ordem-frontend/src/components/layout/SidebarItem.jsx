import { NavLink } from "react-router-dom";

export function SidebarButton({ icon: Icon, label, onClick }) {
  return (
    <button
      onClick={onClick}
      className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-white/70 transition hover:bg-navy-light hover:text-white"
    >
      <Icon size={20} />
      {label}
    </button>
  );
}

export function SidebarLink({ icon: Icon, label, to, onClick }) {
  return (
    <NavLink
      to={to}
      onClick={onClick}
      className={({ isActive }) =>
        `flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition ${
          isActive
            ? "bg-navy-light text-white"
            : "text-white/70 hover:bg-navy-light hover:text-white"
        }`
      }
    >
      <Icon size={20} />
      {label}
    </NavLink>
  );
}
