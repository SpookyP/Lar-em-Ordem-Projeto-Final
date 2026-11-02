import { cx } from "./styles";

export function StepProgress({ current, total, totalKnown = true }) {
  return (
    <div className="flex flex-col gap-2">
      <div className="flex gap-1.5" aria-hidden="true">
        {Array.from({ length: total }, (_, i) => (
          <span
            key={i}
            className={cx(
              "h-1.5 flex-1 rounded-full transition-colors duration-300",
              i <= current ? "bg-navy" : "bg-border",
            )}
          />
        ))}
      </div>
      <p className="text-xs font-medium text-muted">
        {totalKnown
          ? `Passo ${current + 1} de ${total}`
          : `Passo ${current + 1}`}{" "}
        {totalKnown
          ? `Passo ${current + 1} de ${total}`
          : `Passo ${current + 1}`}
      </p>
    </div>
  );
}
