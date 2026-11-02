export function DataRow({ label, value, className = "" }) {
  return (
    <div className={`flex flex-col ${className}`}>
      <dt className="text-muted">{label}</dt>
      <dd className="whitespace-pre-line break-words text-text">{value || "—"}</dd>
    </div>
  );
}
