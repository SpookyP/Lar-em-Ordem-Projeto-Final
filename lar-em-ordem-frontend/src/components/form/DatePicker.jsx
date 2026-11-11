import { useState } from "react";
import { Popover, PopoverButton, PopoverPanel } from "@headlessui/react";
import { CalendarDays, ChevronLeft, ChevronRight, X } from "lucide-react";
import { FormField } from "./FormField";
import { cx, inputClass } from "./styles";

const LOCALE = "pt-PT";
const WEEKDAYS = ["S", "T", "Q", "Q", "S", "S", "D"]; // Monday first
const WEEKDAY_NAMES = ["segunda", "terça", "quarta", "quinta", "sexta", "sábado", "domingo"];

// dates travel as "YYYY-MM-DD" (local day), like a native date input
const pad = (n) => String(n).padStart(2, "0");
const toKey = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
const todayKey = () => new Date().toLocaleDateString("sv-SE");

function parse(key) {
  const [y, m] = (key || todayKey()).split("-").map(Number);
  return { year: y, month: m - 1 };
}

function shownValue(key) {
  if (!key) return "";
  const [y, m, d] = key.split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString(LOCALE, {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

// the weeks of a month, Monday first, with empty cells before day 1
function monthGrid(year, month) {
  const offset = (new Date(year, month, 1).getDay() + 6) % 7;
  const days = new Date(year, month + 1, 0).getDate();
  const cells = [...Array(offset).fill(null), ...Array.from({ length: days }, (_, i) => i + 1)];
  while (cells.length % 7) cells.push(null);
  return cells;
}

function Calendar({ value, min, max, onPick }) {
  const [view, setView] = useState(() => parse(value || (min && min > todayKey() ? min : "")));
  const today = todayKey();

  const title = new Date(view.year, view.month, 1).toLocaleDateString(LOCALE, {
    month: "long",
    year: "numeric",
  });

  function move(delta) {
    setView(({ year, month }) => {
      const next = new Date(year, month + delta, 1);
      return { year: next.getFullYear(), month: next.getMonth() };
    });
  }

  const firstOfMonth = toKey(view.year, view.month, 1);
  const lastOfMonth = toKey(view.year, view.month, new Date(view.year, view.month + 1, 0).getDate());

  return (
    <div className="flex w-72 flex-col gap-3">
      <div className="flex items-center justify-between">
        <button
          type="button"
          onClick={() => move(-1)}
          disabled={Boolean(min) && firstOfMonth <= min}
          aria-label="Mês anterior"
          className="rounded-lg p-1.5 text-muted transition hover:bg-surface hover:text-navy disabled:opacity-30"
        >
          <ChevronLeft size={18} />
        </button>
        <span className="font-medium capitalize text-navy" aria-live="polite">
          {title}
        </span>
        <button
          type="button"
          onClick={() => move(1)}
          disabled={Boolean(max) && lastOfMonth >= max}
          aria-label="Mês seguinte"
          className="rounded-lg p-1.5 text-muted transition hover:bg-surface hover:text-navy disabled:opacity-30"
        >
          <ChevronRight size={18} />
        </button>
      </div>

      <div className="grid grid-cols-7 gap-1 text-center">
        {WEEKDAYS.map((day, i) => (
          <span key={i} className="py-1 text-xs font-medium text-muted" title={WEEKDAY_NAMES[i]}>
            {day}
          </span>
        ))}
        {monthGrid(view.year, view.month).map((day, i) => {
          if (!day) return <span key={`empty-${i}`} />;

          const key = toKey(view.year, view.month, day);
          const disabled = (min && key < min) || (max && key > max);
          const selected = key === value;

          return (
            <button
              key={key}
              type="button"
              disabled={Boolean(disabled)}
              onClick={() => onPick(key)}
              aria-pressed={selected}
              aria-label={shownValue(key)}
              className={cx(
                "flex h-9 items-center justify-center rounded-lg text-sm transition outline-none focus-visible:ring-4 focus-visible:ring-navy/10",
                selected
                  ? "bg-navy font-semibold text-white"
                  : key === today
                    ? "font-semibold text-navy ring-1 ring-navy/30 hover:bg-navy/10"
                    : "text-text hover:bg-surface",
                "disabled:cursor-not-allowed disabled:text-muted/40 disabled:hover:bg-transparent",
              )}
            >
              {day}
            </button>
          );
        })}
      </div>
    </div>
  );
}

/**
 * A date field with a calendar that matches the rest of the forms.
 * value and onChange use "YYYY-MM-DD"; min/max use the same format.
 */
export function DatePicker({
  label,
  hint,
  error,
  required,
  className,
  value,
  onChange,
  min,
  max,
  placeholder = "Escolher data",
  clearable = !required,
}) {
  const today = todayKey();
  const todayAllowed = (!min || today >= min) && (!max || today <= max);

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
            <span className={value ? "text-text" : "text-muted/70"}>
              {value ? shownValue(value) : placeholder}
            </span>
          </PopoverButton>
          <CalendarDays
            size={18}
            aria-hidden="true"
            className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"
          />
          {clearable && value && (
            <button
              type="button"
              onClick={() => onChange("")}
              aria-label="Limpar data"
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
            <>
              <Calendar
                value={value}
                min={min}
                max={max}
                onPick={(key) => {
                  onChange(key);
                  close();
                }}
              />
              <div className="mt-3 flex items-center justify-between border-t border-border pt-3 text-sm">
                {clearable ? (
                  <button
                    type="button"
                    onClick={() => {
                      onChange("");
                      close();
                    }}
                    className="text-muted hover:text-navy"
                  >
                    Limpar
                  </button>
                ) : (
                  <span />
                )}
                {todayAllowed && (
                  <button
                    type="button"
                    onClick={() => {
                      onChange(today);
                      close();
                    }}
                    className="font-medium text-navy hover:underline"
                  >
                    Hoje
                  </button>
                )}
              </div>
            </>
          )}
        </PopoverPanel>
      </Popover>
    </FormField>
  );
}