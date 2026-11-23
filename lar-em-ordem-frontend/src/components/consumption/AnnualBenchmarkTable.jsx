import { useEffect, useState } from "react";
import { AlertTriangle, Info, RefreshCw } from "lucide-react";
import { fetchAnnualBenchmarks } from "../../services/api/invoiceService";
import {
  formatMoney,
  formatMoneySigned,
  formatNumber,
} from "../../utils/format";
import {
  HEADER_COLOR,
  getServiceTheme,
  isGeneralService,
} from "./benchmarkShared";
import { EstimateInfo } from "./EstimateInfo";
import { AnnualCostsChart } from "./AnnualCostsChart";

function isMigrationError(err) {
  const payload = err?.response?.data ?? {};
  const errorCode = [
    payload?.code,
    payload?.error_code,
    payload?.error?.code,
    payload?.sql_state,
    payload?.sqlState,
    payload?.sqlstate,
  ]
    .filter(Boolean)
    .join(" ")
    .toUpperCase();

  const errorText = [
    payload?.message,
    payload?.error,
    err?.message,
    ...(payload?.errors && typeof payload.errors === "object"
      ? Object.values(payload.errors).flat()
      : []),
  ]
    .filter((value) => typeof value === "string")
    .join(" ");

  const migrationCode =
    /MIGRATIONS?_PENDING|MIGRATION_REQUIRED|SCHEMA_NOT_UP_TO_DATE|DATABASE_SCHEMA_OUTDATED/.test(
      errorCode,
    );

  const schemaSqlCode =
    /\b(42P01|42703|42S02|42S22|3F000|1146|1054|207|208)\b/i.test(errorCode) ||
    /SQLSTATE\[(42P01|42703|42S02|42S22|3F000)\]/i.test(errorText);

  const missingSchemaObject =
    /no such (table|column)/i.test(errorText) ||
    /unknown column/i.test(errorText) ||
    /undefined_(table|column)/i.test(errorText) ||
    /(relation|table|column|schema)\s+["'`\w.]+["'`\s]*\s+(does not exist|doesn't exist|not found)/i.test(
      errorText,
    ) ||
    /invalid object name/i.test(errorText);

  return migrationCode || schemaSqlCode || missingSchemaObject;
}

function getErrorPresentation(err) {
  if (isMigrationError(err)) {
    return {
      type: "migration",
      title: "Atualização da base de dados necessária",
      description:
        "A comparação anual está temporariamente indisponível porque a estrutura da base de dados precisa de ser atualizada. Contacta o administrador do sistema para concluir esta atualização.",
    };
  }
  return {
    type: "general",
    title: "Não foi possível carregar a comparação anual",
    description:
      "Ocorreu um problema ao obter os dados do benchmark. Tenta novamente dentro de alguns instantes.",
  };
}

function getAnnualRowPresentation(row, isGeneralView, options) {
  const userEstimated = Number(row.estimated_months_count) > 0;
  const benchmarkEstimated = row.benchmark_estimated === true;
  const difference = Number(row.difference ?? 0);

  const differenceStyle =
    difference < 0
      ? "text-emerald-700"
      : difference > 0
        ? "text-red-700"
        : "text-slate-600";

  const differencePillStyle =
    difference < 0
      ? "bg-emerald-50 text-emerald-700"
      : difference > 0
        ? "bg-red-50 text-red-700"
        : "bg-slate-100 text-slate-600";

  const estimatedServices = isGeneralView ? (row.estimated_services ?? []) : [];

  const hasRowEstimate = isGeneralView
    ? estimatedServices.length > 0
    : userEstimated || benchmarkEstimated;

  const estimateEntries = isGeneralView
    ? estimatedServices.map((detail) => {
        const option = options.find(
          (item) => String(item.value) === String(detail.consumption_type_id),
        ) ?? {
          value: String(detail.consumption_type_id),
          label: detail.consumption_type ?? "Serviço",
        };

        return {
          label: option.label,
          option,
          details: [],
        };
      })
    : [
        {
          label: null,
          details: [
            ...(userEstimated
              ? [
                  {
                    label: "Consumo",
                    text: `${row.estimated_months_count} meses estimados`,
                  },
                ]
              : []),
            ...(benchmarkEstimated
              ? [
                  {
                    label: "Benchmark",
                    text: `projetado${
                      Number(row.benchmark_estimated_months_count) > 0
                        ? ` (${row.benchmark_estimated_months_count} meses)`
                        : ""
                    }`,
                  },
                ]
              : []),
          ],
        },
      ];
  return {
    difference,
    differenceStyle,
    differencePillStyle,
    hasRowEstimate,
    estimateEntries,
  };
}

export default function AnnualBenchmarkTable({ propertyId, refreshKey }) {
  const [serviceSelection, setServiceSelection] = useState({
    propertyId: null,
    value: "all",
  });

  const [retryCount, setRetryCount] = useState(0);
  const requestedService =
    serviceSelection.propertyId === propertyId ? serviceSelection.value : "all";

  const [fetched, setFetched] = useState({
    key: null,
    propertyId: null,
    data: null,
    error: null,
  });

  const requestKey = `${propertyId ?? ""}-${refreshKey ?? 0}-${retryCount}`;
  const current = propertyId && fetched.key === requestKey ? fetched : null;

  const display =
    current ??
    (propertyId && fetched.propertyId === propertyId ? fetched : null);

  const result = display?.data ?? null;
  const error = current?.error ?? null;
  const loading = Boolean(propertyId) && current === null;

  useEffect(() => {
    if (!propertyId) return undefined;
    let cancelled = false;

    fetchAnnualBenchmarks(propertyId)
      .then((data) => {
        if (!cancelled) {
          setFetched({ key: requestKey, propertyId, data, error: null });
        }
      })
      .catch((err) => {
        console.error(
          "[AnnualBenchmarkTable] Erro ao carregar o benchmark:",
          err,
        );
        if (!cancelled) {
          setFetched({
            key: requestKey,
            propertyId,
            data: null,
            error: getErrorPresentation(err),
          });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [propertyId, requestKey]);

  const options = result?.service_options ?? [];
  const hasGeneralOption = options.some(isGeneralService);
  const requestedServiceIsAvailable = options.some(
    (option) => String(option.value) === String(requestedService),
  );

  const activeService =
    result && !requestedServiceIsAvailable
      ? hasGeneralOption
        ? "all"
        : (options.find((option) => !isGeneralService(option))?.value ?? "all")
      : requestedService;

  const isGeneralView = isGeneralService({ value: activeService });
  const rows = isGeneralView
    ? (result?.data ?? [])
    : (result?.services?.[String(activeService)]?.data ?? []);

  function selectService(value) {
    setServiceSelection({
      propertyId,
      value,
    });
  }

  function retryRequest() {
    setRetryCount((count) => count + 1);
  }
  if (
    propertyId &&
    !loading &&
    !error &&
    rows.length === 0 &&
    options.length === 0
  ) {
    return null;
  }

  return (
    <section className="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      {/* Cabeçalho principal com os filtros de serviço integrados. */}
      <header
        className="relative -mb-px overflow-hidden border-b border-white/20 px-5 py-5 sm:px-6"
        style={{
          backgroundColor: HEADER_COLOR,
          borderColor: "rgba(255,255,255,0.2)",
        }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-40 w-40 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-black/10" />
        <div className="pointer-events-none absolute right-1/4 top-5 h-16 w-16 rounded-full border border-white/10" />

        <div className="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold tracking-tight text-white">
              Comparação anual com benchmarks
            </h2>
            <p className="mt-1 text-base text-white/75">
              Comparação dos gastos anuais da habitação com a média anual de
              habitações similares.
            </p>
          </div>

          {options.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {options.map((option) => {
                const selected = String(activeService) === String(option.value);
                const theme = getServiceTheme(option);
                const Icon = theme.Icon;

                return (
                  <button
                    key={option.value}
                    type="button"
                    onClick={() => selectService(option.value)}
                    aria-pressed={selected}
                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-2 text-base font-medium transition ${
                      selected
                        ? theme.selectedClass
                        : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                    }`}
                    style={selected ? undefined : { color: "#FFFFFF" }}
                  >
                    <Icon size={18} strokeWidth={2} aria-hidden="true" />
                    <span>{option.label}</span>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </header>

      {error && (
        <div className="px-5 pt-5 pb-5 sm:px-6" role="alert" aria-live="polite">
          <div
            className={`flex items-start gap-3 rounded-xl border p-4 ${
              error.type === "migration"
                ? "border-amber-200 bg-amber-50"
                : "border-red-200 bg-red-50"
            }`}
          >
            <div
              className={`mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                error.type === "migration"
                  ? "bg-amber-100 text-amber-700"
                  : "bg-red-100 text-red-700"
              }`}
            >
              <AlertTriangle size={21} aria-hidden="true" />
            </div>

            <div className="min-w-0 flex-1">
              <h3
                className={`text-base font-semibold ${
                  error.type === "migration" ? "text-amber-900" : "text-red-900"
                }`}
              >
                {error.title}
              </h3>
              <p
                className={`mt-1 text-sm leading-relaxed ${
                  error.type === "migration" ? "text-amber-800" : "text-red-800"
                }`}
              >
                {error.description}
              </p>

              {error.type !== "migration" && (
                <button
                  type="button"
                  onClick={retryRequest}
                  className="mt-3 inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-800 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1"
                >
                  <RefreshCw size={15} aria-hidden="true" />
                  Tentar novamente
                </button>
              )}
            </div>
          </div>
        </div>
      )}

      {!propertyId && !error && (
        <div className="px-5 py-12 text-center text-base text-slate-500 sm:px-6">
          Seleciona uma casa para ver a comparação anual.
        </div>
      )}

      {propertyId && loading && rows.length === 0 && (
        <div className="flex items-center justify-center gap-2 px-5 py-12 text-base text-slate-500 sm:px-6">
          <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-slate-600" />
          A carregar comparação anual...
        </div>
      )}

      {propertyId && !loading && !error && rows.length === 0 && (
        <div className="px-5 py-12 text-center text-base text-slate-500 sm:px-6">
          Ainda não existem dados anuais para este serviço.
        </div>
      )}

      {/* Tabela e gráfico apresentados quando existem dados. */}
      {rows.length > 0 && (
        <>
          <div className="space-y-3 px-4 py-4 lg:hidden">
            {rows.map((row) => {
              const {
                difference,
                differenceStyle,
                differencePillStyle,
                hasRowEstimate,
                estimateEntries,
              } = getAnnualRowPresentation(row, isGeneralView, options);

              return (
                <article
                  key={`mobile-${row.year}`}
                  className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                  <div className="flex items-center justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-2">
                      <span className="rounded-lg bg-navy/10 px-3 py-1.5 text-base font-bold tabular-nums text-navy">
                        {row.year}
                      </span>
                      {hasRowEstimate && (
                        <EstimateInfo
                          entries={estimateEntries}
                          general={isGeneralView}
                        />
                      )}
                    </div>
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                      Comparação anual
                    </span>
                  </div>

                  <div className="mt-4 grid grid-cols-2 gap-3">
                    <div className="min-w-0 rounded-lg bg-slate-50 p-3">
                      <p className="text-xs font-semibold uppercase leading-4 tracking-wide text-slate-500">
                        Gasto da habitação
                      </p>
                      <p className="mt-1 wrap-break-word text-base font-bold tabular-nums text-slate-900">
                        {formatMoney(row.user_total)}
                      </p>
                    </div>

                    <div className="min-w-0 rounded-lg bg-slate-50 p-3">
                      <p className="text-xs font-semibold uppercase leading-4 tracking-wide text-slate-500">
                        Benchmark médio
                      </p>
                      <p className="mt-1 wrap-break-word text-base font-bold tabular-nums text-slate-800">
                        {formatMoney(row.benchmark_total)}
                      </p>
                      {row.sample_size != null && (
                        <p className="mt-1 text-xs leading-4 text-slate-400">
                          Amostra de {row.sample_size} habitações
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <span className="text-sm font-semibold text-slate-500">
                      Diferença
                    </span>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                      <span
                        className={`text-base font-bold tabular-nums ${differenceStyle}`}
                      >
                        {formatMoneySigned(difference)}
                      </span>
                      {row.difference_percentage != null && (
                        <span
                          className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-semibold tabular-nums ${differencePillStyle}`}
                        >
                          {formatNumber(row.difference_percentage, {
                            decimals: 1,
                            signed: true,
                          })}
                          %
                        </span>
                      )}
                    </div>
                  </div>
                </article>
              );
            })}
          </div>

          <div className="hidden w-full overflow-x-auto lg:block">
            <table className="w-full min-w-155 border-collapse text-base">
              <thead
                className="text-white"
                style={{
                  backgroundColor: HEADER_COLOR,
                  backgroundImage: `
                    radial-gradient(circle at 8% 50%, rgba(255,255,255,0.09) 0 32px, transparent 33px),
                    radial-gradient(circle at 96% 120%, rgba(0,0,0,0.12) 0 48px, transparent 49px)
                  `,
                }}
              >
                <tr className="text-xs font-semibold uppercase tracking-wide text-white">
                  <th className="px-5 py-4 text-left sm:px-6">Ano</th>
                  <th className="px-4 py-4 text-right">
                    Gasto da habitação (€)
                  </th>
                  <th className="px-4 py-4 text-right">Benchmark médio (€)</th>
                  <th className="px-5 py-4 text-right sm:px-6">
                    Diferença (€)
                  </th>
                </tr>
              </thead>

              <tbody>
                {rows.map((row) => {
                  const {
                    difference,
                    differenceStyle,
                    differencePillStyle,
                    hasRowEstimate,
                    estimateEntries,
                  } = getAnnualRowPresentation(row, isGeneralView, options);

                  return (
                    <tr
                      key={row.year}
                      className="border-b border-slate-100 transition-colors last:border-b-0 hover:bg-slate-50/60"
                    >
                      <td className="px-5 py-5 sm:px-6">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold tabular-nums text-slate-800">
                            {row.year}
                          </span>
                          {hasRowEstimate && (
                            <EstimateInfo
                              entries={estimateEntries}
                              general={isGeneralView}
                            />
                          )}
                        </div>
                      </td>

                      <td className="px-4 py-5 text-right font-semibold tabular-nums text-slate-900">
                        {formatMoney(row.user_total)}
                      </td>

                      <td className="px-4 py-5 text-right font-semibold tabular-nums text-slate-800">
                        {formatMoney(row.benchmark_total)}
                        {row.sample_size != null && (
                          <p className="mt-1 text-sm font-normal text-slate-400">
                            Amostra de {row.sample_size} habitações
                          </p>
                        )}
                      </td>

                      <td className="px-5 py-5 text-right sm:px-6">
                        <span
                          className={`font-semibold tabular-nums ${differenceStyle}`}
                        >
                          {formatMoneySigned(difference)}
                        </span>
                        {row.difference_percentage != null && (
                          <div className="mt-1.5">
                            <span
                              className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-semibold tabular-nums ${differencePillStyle}`}
                            >
                              {formatNumber(row.difference_percentage, {
                                decimals: 1,
                                signed: true,
                              })}
                              %
                            </span>
                          </div>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Gráfico anual de barras. */}
          <AnnualCostsChart rows={rows} service={activeService} />
          <footer className="border-t border-slate-100 px-5 py-4 sm:px-6">
            <p className="mb-3 text-sm leading-relaxed text-slate-500">
              Uma diferença negativa significa que a habitação gastou menos do
              que o benchmark. Uma diferença positiva significa que gastou mais.
            </p>

            <div className="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3">
              <Info
                size={19}
                className="mt-0.5 shrink-0 text-slate-500"
                aria-hidden="true"
              />
              <p className="text-sm leading-relaxed text-slate-600">
                <span className="font-semibold text-slate-700">
                  Valores indicativos.
                </span>{" "}
                Os benchmarks são médias de habitações semelhantes e não valores
                exatos. Os anos ainda incompletos podem incluir projeções
                anuais, tanto nos consumos como nos benchmarks, pelo que os
                totais podem diferir dos custos efetivamente registados nas
                faturas.
              </p>
            </div>
          </footer>
        </>
      )}
    </section>
  );
}
