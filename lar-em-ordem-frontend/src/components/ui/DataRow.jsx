export function DataRow({ label, value }) {
  return (
    <div className="flex flex-col">
      <dt className="text-muted">{label}</dt>
      <dd className="text-text">{value || "—"}</dd>
    </div>
  );
}
