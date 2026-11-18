import { Popover, PopoverButton, PopoverPanel } from "@headlessui/react";
import { Clock, X } from "lucide-react";
import { FormField } from "./FormField";
import { cx, inputClass } from "./styles";

const pad = (n) => String(n).padStart(2, "0");
const HOURS = Array.from({ length: 24 }, (_, i) => pad(i));

function Column({ title, options, selected, onPick }) {
  return (
    <div className="flex flex-col gap-2">
      <p className="text-center text-xs font-medium text-muted">{title}</p>
      <div className="flex max-h-52 w-16 flex-col gap-1 overflow-y-auto pr-1">
        {options.map((option) => (
          <button
            key={option}
            type="button"
            onClick={() => onPick(option)}
            aria-pressed={option === selected}
            className={cx(
              "rounded-lg py-1.5 text-sm transition",
              option === selected
                ? "bg-navy font-semibold text-white"
                : "text-text hover:bg-surface",
            )}
          >
            {option}
          </button>
        ))}
      </div>
    </div>
  );
}

/**
 * Time input with the team's look. value and onChange use "HH:MM", like a native time input.
 * minuteStep sets which minutes can be picked (5 gives 00, 05, 10...).
 */
export function TimePicker({
  label,
  hint,
  error,
  required,
  className,
  value,
  onChange,
  minuteStep = 5,
  placeholder = "Escolher hora",
  clearable = !required,
}) {
  const [hour = "", minute = ""] = (value || "").split(":");
  const minutes = Array.from({ length: Math.ceil(60 / minuteStep) }, (_, i) =>
    pad(i * minuteStep),
  );

  return (
    <FormField label={label} hint={hint} error={error} required={required} className={className}>
      <Popover className="relative">
        <div className="relative">
          <PopoverButton
            aria-label={label}
            className={cx(
              inputClass(Boolean(error)),
              "flex items-center gap-2 pl-10 text-left",
              clearable && value && "pr-9",
            )}
          >
            <span className={value ? "text-text" : "text-muted/70"}>{value || placeholder}</span>
          </PopoverButton>
          <Clock
            size={18}
            aria-hidden="true"
            className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"
          />
          {clearable && value && (
            <button
              type="button"
              onClick={() => onChange("")}
              aria-label="Limpar hora"
              className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1 text-muted transition hover:bg-surface hover:text-navy"
            >
              <X size={15} />
            </button>
          )}
        </div>

        <PopoverPanel
          anchor="bottom start"
          transition
          className="z-50 rounded-2xl border border-border bg-card p-4 shadow-lg [--anchor-gap:6px] transition duration-100 ease-out data-closed:opacity-0"
        >
          {({ close }) => (
            <div className="flex gap-3">
              <Column
                title="Hora"
                options={HOURS}
                selected={hour}
                onPick={(picked) => onChange(`${picked}:${minute || "00"}`)}
              />
              <Column
                title="Minutos"
                options={minutes}
                selected={minute}
                onPick={(picked) => {
                  onChange(`${hour || "00"}:${picked}`);
                  close();
                }}
              />
            </div>
          )}
        </PopoverPanel>
      </Popover>
    </FormField>
  );
}