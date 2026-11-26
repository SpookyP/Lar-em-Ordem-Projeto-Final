import { useState } from "react";
import { monthKey, keyToIndex } from "../../utils/invoiceTimelineUtils";
import { MONTHS, monthLongLabel, MAX_MONTH_DISTANCE } from "./timelineShared";

export function DateRangeSelects({ range, currentMonthIndex, onChange }) {
  const [draft, setDraft] = useState(range);
  const [error, setError] = useState("");

  const currentYear = Math.floor(currentMonthIndex / 12);
  const currentMonth = currentMonthIndex % 12;

  const years = Array.from(
    { length: currentYear - 1899 },
    (_, index) => currentYear - index,
  );

  function updateDate(field, part, value) {
    const [year, month] = draft[field].split("-").map(Number);

    let nextYear = year;
    let nextMonth = month - 1;

    if (part === "year") {
      nextYear = Number(value);

      if (nextYear === currentYear && nextMonth > currentMonth) {
        nextMonth = currentMonth;
      }
    } else {
      nextMonth = Number(value);
    }

    const next = {
      ...draft,
      [field]: monthKey(nextYear, nextMonth),
    };

    setDraft(next);

    const startIndex = keyToIndex(next.start);
    const endIndex = keyToIndex(next.end);

    if (startIndex > currentMonthIndex || endIndex > currentMonthIndex) {
      setError("Não pode selecionar uma data futura.");
      return;
    }

    if (endIndex < startIndex) {
      setError("A data final não pode ser anterior à data inicial.");
      return;
    }

    const distance = Math.abs(endIndex - startIndex);

    if (distance > MAX_MONTH_DISTANCE) {
      setError(
        "O intervalo máximo é de 25 meses, contando com o mês inicial e o mês final.",
      );
      return;
    }

    setError("");
    onChange(next);
  }

  function renderDateSelector(field, label, tone) {
    const [year, month] = draft[field].split("-").map(Number);

    const availableMonths = MONTHS.map((name, index) => ({
      value: index,
      label: name,
    })).filter(({ value }) => year < currentYear || value <= currentMonth);

    const palette =
      tone === "indigo"
        ? {
          icon: "bg-indigo-100 text-indigo-700",
          dot: "bg-indigo-700",
          focus: "focus:border-indigo-400 focus:ring-indigo-100",
        }
        : {
          icon: "bg-teal-100 text-teal-700",
          dot: "bg-teal-500",
          focus: "focus:border-teal-400 focus:ring-teal-100",
        };

    return (
      <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:shadow-md sm:p-4">
        <div className="mb-3 flex items-center gap-2.5">
          <span
            className={`flex h-9 w-9 items-center justify-center rounded-xl ${palette.icon}`}
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-4.5[18px]"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
              aria-hidden="true"
            >
              <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
              <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" />
              <path d="M8 13h.01M12 13h.01M16 13h.01M8 16.5h.01M12 16.5h.01" />
            </svg>
          </span>

          <div className="min-w-0">
            <label className="block text-base font-semibold text-slate-800">
              {label}
            </label>

            <span className="mt-0.5 flex items-center gap-1.5 text-[13px] text-slate-400">
              <span className={`h-1.5 w-1.5 rounded-full ${palette.dot}`} />
              {monthLongLabel(draft[field])}
            </span>
          </div>
        </div>

        <div className="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
          <div>
            <span className="mb-1.5 block text-xs font-bold uppercase tracking-[0.08em] text-slate-400">
              Mês
            </span>

            <select
              value={month - 1}
              onChange={(event) =>
                updateDate(field, "month", event.target.value)
              }
              aria-label={`${label}: mês`}
              className={`h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base font-medium text-slate-700 outline-none transition hover:border-slate-300 hover:bg-white focus:bg-white focus:ring-4 ${palette.focus}`}
            >
              {availableMonths.map(({ value, label: monthName }) => (
                <option key={value} value={value}>
                  {monthName}
                </option>
              ))}
            </select>
          </div>

          <div>
            <span className="mb-1.5 block text-xs font-bold uppercase tracking-[0.08em] text-slate-400">
              Ano
            </span>

            <select
              value={year}
              onChange={(event) =>
                updateDate(field, "year", event.target.value)
              }
              aria-label={`${label}: ano`}
              className={`h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base font-medium text-slate-700 outline-none transition hover:border-slate-300 hover:bg-white focus:bg-white focus:ring-4 ${palette.focus}`}
            >
              {years.map((yearOption) => (
                <option key={yearOption} value={yearOption}>
                  {yearOption}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {renderDateSelector("start", "Data inicial", "indigo")}
        {renderDateSelector("end", "Data final", "teal")}
      </div>

      {error && (
        <div
          role="alert"
          className="mt-3 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm leading-relaxed text-rose-700"
        >
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-rose-100">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-4 w-4"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              aria-hidden="true"
            >
              <path d="M12 8v5M12 16.5h.01" />
              <circle cx="12" cy="12" r="9" />
            </svg>
          </span>

          <span>
            <strong className="font-semibold">Intervalo inválido.</strong>{" "}
            {error}
          </span>
        </div>
      )}
    </div>
  );
}
