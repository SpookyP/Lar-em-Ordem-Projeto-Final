export function LoadingState({ label = "A carregar...", className = "" }) {
  return <p className={`text-sm text-muted ${className}`.trim()}>{label}</p>;
}
