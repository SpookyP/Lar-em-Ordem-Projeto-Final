import { ChevronRight } from 'lucide-react';
import { Link } from 'react-router-dom';

export function ShortcutCard({ icon: Icon, iconColor, title, description, to }) {
  return (
    <Link
      to={to}
      className="flex items-center gap-3 rounded-2xl bg-card p-4 shadow-sm transition hover:shadow-md"
    >
      <div className={`flex items-center justify-center rounded-xl p-2.5 ${iconColor}`}>
        <Icon size={22} />
      </div>

      <div className="flex-1">
        <p className="font-medium text-navy">{title}</p>
        <p className="text-sm text-muted">{description}</p>
      </div>

      <ChevronRight size={20} className="text-muted" />
    </Link>
  );
}