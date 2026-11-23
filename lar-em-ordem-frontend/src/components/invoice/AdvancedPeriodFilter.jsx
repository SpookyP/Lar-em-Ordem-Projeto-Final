import { monthsBetween, indexToKey } from "../../utils/invoiceTimelineUtils";
import { MAX_MONTH_DISTANCE, presetRange } from "./timelineShared";
import { DateRangeSelects } from "./DateRangeSelects";

export function AdvancedPeriodFilter({
  advancedEnabled,
  setAdvancedEnabled,
  range,
  currentMonthIndex,
  latestDataIndex,
  initialAdvancedRange,
  updateRange,
}) {
  return (
    <div className="mt-5 border-t border-slate-100 pt-5">
      <label
        className={`group flex cursor-pointer items-center gap-3.5 rounded-2xl border p-3.5 transition-all duration-200 sm:p-4 ${
          advancedEnabled
            ? "border-navy/20 bg-linear-to-r from-navy/[0.07] via-white to-navy/4 shadow-sm shadow-navy/10"
            : "border-slate-200 bg-white hover:border-navy/30 hover:bg-navy/2.5"
        }`}
      >
        <span
          className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition ${
            advancedEnabled
              ? "bg-navy text-white shadow-md shadow-navy/20"
              : "bg-slate-100 text-slate-500 group-hover:bg-navy/10 group-hover:text-navy"
          }`}
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            className="h-5 w-5"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
            <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h3M8 16.5h7" />
          </svg>
        </span>

        {/* Ativa ou desativa a seleção personalizada de datas. */}
        <input
          type="checkbox"
          checked={advancedEnabled}
          onChange={(event) => {
            const enabled = event.target.checked;

            setAdvancedEnabled(enabled);

            if (enabled) {
              updateRange(initialAdvancedRange);
            } else {
              const defaultEndIndex = Math.min(
                latestDataIndex ?? currentMonthIndex,
                currentMonthIndex,
              );

              const defaultMonthKeys = monthsBetween(
                indexToKey(defaultEndIndex - MAX_MONTH_DISTANCE),
                indexToKey(defaultEndIndex),
              );

              updateRange(presetRange(defaultMonthKeys, 1));
            }
          }}
          className="h-4 w-4 shrink-0 rounded border-slate-300 text-navy focus:ring-navy"
        />

        <span className="min-w-0 flex-1">
          <span className="flex flex-wrap items-center gap-2">
            <span className="text-base font-bold text-slate-800">
              Filtro avançado de período
            </span>

            <span
              className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase tracking-wide ${
                advancedEnabled
                  ? "bg-navy/10 text-navy"
                  : "bg-slate-100 text-slate-500"
              }`}
            >
              {advancedEnabled ? "Ativo" : "Desativado"}
            </span>
          </span>

          <span className="mt-1 block text-sm leading-relaxed text-slate-500">
            Define um intervalo personalizado para explorar a evolução dos teus
            consumos.
          </span>
        </span>

        <svg
          viewBox="0 0 24 24"
          fill="none"
          className={`h-5 w-5 shrink-0 text-slate-400 transition-transform ${
            advancedEnabled ? "rotate-180 text-navy" : ""
          }`}
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          aria-hidden="true"
        >
          <path d="m6 9 6 6 6-6" />
        </svg>
      </label>

      {/* Seletores de datas apresentados quando o filtro avançado está ativo. */}
      {advancedEnabled && (
        <div className="relative mt-3 overflow-hidden rounded-2xl border border-navy/15 bg-linear-to-br from-navy/5.5 via-white to-navy/[0.035] p-4 shadow-sm sm:p-5">
          <div className="pointer-events-none absolute -right-10 -top-12 h-36 w-36 rounded-full bg-navy/6 blur-2xl" />
          <div className="pointer-events-none absolute -bottom-16 left-1/3 h-36 w-36 rounded-full bg-navy/4.5 blur-2xl" />

          <div className="relative">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
              <div className="flex items-center gap-2.5">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    className="h-4.5 w-4.5"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M4 19V5M4 19h16" />
                    <path d="m7 15 3-4 3 2 5-7" />
                  </svg>
                </span>

                <div>
                  <p className="text-base font-bold text-slate-800">
                    Período personalizado
                  </p>

                  <p className="mt-0.5 text-sm text-slate-500">
                    Escolhe as datas que queres comparar.
                  </p>
                </div>
              </div>

              <span className="inline-flex items-center gap-1.5 rounded-full border border-navy/15 bg-white/90 px-3 py-1.5 text-[13px] font-bold text-navy shadow-sm">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  className="h-3.5 w-3.5"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 7v5l3 2" />
                </svg>
                Até 25 meses
              </span>
            </div>

            <DateRangeSelects
              key={`${range.start}-${range.end}`}
              range={range}
              currentMonthIndex={currentMonthIndex}
              onChange={updateRange}
            />

            {/* Informação sobre os limites de seleção do período. */}
            <div className="mt-3 flex items-start gap-2.5 rounded-xl border border-white/90 bg-white/75 px-3.5 py-3 shadow-sm">
              <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-navy/10 text-navy">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  className="h-4 w-4"
                  stroke="currentColor"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="m5 12 4 4L19 6" />
                </svg>
              </span>

              <p className="text-[13px] leading-relaxed text-slate-600">
                Podes selecionar qualquer mês e ano desde 1900 até ao mês atual.
                O intervalo máximo é de{" "}
                <strong className="font-semibold text-slate-800">
                  25 meses de calendário, contando o mês inicial e o final
                </strong>
                . O gráfico apresenta sempre os meses por ordem cronológica.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
