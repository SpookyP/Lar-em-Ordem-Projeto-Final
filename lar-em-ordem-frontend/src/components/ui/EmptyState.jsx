export function EmptyState({ message, className = "" }) {
  return <p className={`text-sm text-muted ${className}`.trim()}>{message}</p>;
}
