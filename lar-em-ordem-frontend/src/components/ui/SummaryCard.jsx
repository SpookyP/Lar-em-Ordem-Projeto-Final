export function SummaryCard({ icon: Icon, iconColor, value, label }) {
  return (
    <div className="flex items-center gap-3 rounded-2xl bg-card p-4 shadow-sm">
      <div className={`flex items-center justify-center rounded-xl p-2.5 ${iconColor}`}>
        <Icon size={22} />
      </div>

      <div>
        <p className="font-display text-xl text-navy leading-tight">{value}</p>
        <p className="text-sm text-muted">{label}</p>
      </div>
    </div>
  );
}