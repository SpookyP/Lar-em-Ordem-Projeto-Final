import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import {
  AlertTriangle,
  Droplets,
  FileText,
  Flame,
  Info,
  RefreshCw,
  TrendingDown,
  TrendingUp,
  Zap,
} from "lucide-react";
import { fetchAnnualBenchmarks } from "../../services/api/invoiceService";

// Cor fixa dos cabeçalhos.
const HEADER_COLOR = "#1C3557";

// Formatadores de valores e percentagens.
const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

const compactMoney = new Intl.NumberFormat("pt-PT", {
  notation: "compact",
  maximumFractionDigits: 1,
});

const signedMoney = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
  signDisplay: "exceptZero",
});

const signedPercent = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 1,
  signDisplay: "exceptZero",
});

const percent = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 1,
});

// Identifica falhas de esquema que normalmente indicam migrations em falta.
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
      errorCode
    );

  const schemaSqlCode =
    /\b(42P01|42703|42S02|42S22|3F000|1146|1054|207|208)\b/i.test(
      errorCode
    ) || /SQLSTATE\[(42P01|42703|42S02|42S22|3F000)\]/i.test(errorText);

  const missingSchemaObject =
    /no such (table|column)/i.test(errorText) ||
    /unknown column/i.test(errorText) ||
    /undefined_(table|column)/i.test(errorText) ||
    /(relation|table|column|schema)\s+["'`\w.]+["'`\s]*\s+(does not exist|doesn't exist|not found)/i.test(
      errorText
    ) ||
    /invalid object name/i.test(errorText);

  return migrationCode || schemaSqlCode || missingSchemaObject;
}

// Converte erros técnicos em mensagens adequadas para o utilizador.
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

// Define o ícone e as cores de cada tipo de serviço.
function getServiceTheme(option) {
  const value = String(option?.value ?? "").toLowerCase();
  const label = String(option?.label ?? "").toLowerCase();

  const raw = `${value} ${label}`
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  if (
    /^(all|general|geral|todos)$/.test(value) ||
    /\b(all|general|geral|todos)\b/.test(raw)
  ) {
    return {
      Icon: FileText,
      kind: "all",
      color: "#583D69",
      iconClass: "text-[#583D69]",
      selectedClass: "border-white bg-white text-[#583D69] shadow-sm",
      chipClass: "border-slate-200 bg-slate-50 text-slate-700",
    };
  }

  if (value === "1" || /eletric|electric|energia/.test(raw)) {
    return {
      Icon: Zap,
      kind: "electricity",
      color: "#1C3557",
      iconClass: "text-[#1C3557]",
      selectedClass: "border-white bg-white text-[#1C3557] shadow-sm",
      chipClass: "border-[#1C3557]/20 bg-[#1C3557]/5 text-[#1C3557]",
    };
  }

  if (value === "2" || /agua|water/.test(raw)) {
    return {
      Icon: Droplets,
      kind: "water",
      color: "#0F766E",
      iconClass: "text-[#0F766E]",
      selectedClass: "border-white bg-white text-[#0F766E] shadow-sm",
      chipClass: "border-[#0F766E]/20 bg-[#0F766E]/5 text-[#0F766E]",
    };
  }

  if (value === "3" || /gas/.test(raw)) {
    return {
      Icon: Flame,
      kind: "gas",
      color: "#EA580C",
      iconClass: "text-[#EA580C]",
      selectedClass: "border-white bg-white text-[#EA580C] shadow-sm",
      chipClass: "border-[#EA580C]/20 bg-[#EA580C]/5 text-[#EA580C]",
    };
  }

  return {
    Icon: FileText,
    kind: "other",
    color: "#64748b",
    iconClass: "text-slate-500",
    selectedClass: "border-white bg-white text-slate-600 shadow-sm",
    chipClass: "border-slate-200 bg-slate-50 text-slate-700",
  };
}

// Verifica se a opção corresponde à visão geral dos serviços.
function isGeneralService(option) {
  return getServiceTheme(option).kind === "all";
}

// Informação sobre os valores estimados, apresentada numa janela flutuante.
function EstimateInfo({ entries, general = false }) {
  const [isOpen, setIsOpen] = useState(false);
  const [position, setPosition] = useState(null);

  const buttonRef = useRef(null);
  const popupRef = useRef(null);

  useEffect(() => {
    if (!isOpen) return undefined;

    const updatePosition = () => {
      const anchor = buttonRef.current?.getBoundingClientRect();

      if (!anchor) return;

      const popupWidth = Math.min(340, window.innerWidth - 24);
      const popupHeight = popupRef.current?.offsetHeight ?? 100;

      const left = Math.max(
        12,
        Math.min(anchor.left, window.innerWidth - popupWidth - 12)
      );

      const below = anchor.bottom + 8;

      const top =
        below + popupHeight <= window.innerHeight - 12
          ? below
          : Math.max(12, anchor.top - popupHeight - 8);

      setPosition({
        top,
        left,
        width: popupWidth,
      });
    };

    const frame = window.requestAnimationFrame(updatePosition);

    // Fecha quando se toca fora do botão ou da janela.
    const handleOutsideClick = (event) => {
      const target = event.target;

      if (
        buttonRef.current?.contains(target) ||
        popupRef.current?.contains(target)
      ) {
        return;
      }

      setIsOpen(false);
    };

    // Fecha com Escape.
    const handleKeyDown = (event) => {
      if (event.key === "Escape") {
        setIsOpen(false);
      }
    };

    document.addEventListener("pointerdown", handleOutsideClick);
    document.addEventListener("keydown", handleKeyDown);

    window.addEventListener("resize", updatePosition);
    window.addEventListener("scroll", updatePosition, true);

    return () => {
      window.cancelAnimationFrame(frame);
      document.removeEventListener("pointerdown", handleOutsideClick);
      document.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("resize", updatePosition);
      window.removeEventListener("scroll", updatePosition, true);
    };
  }, [isOpen]);

  if (!entries?.length) return null;

  const popup =
    isOpen && typeof document !== "undefined"
      ? createPortal(
          <div
            ref={popupRef}
            role="tooltip"
            className="fixed z-[1000] max-h-[70vh] overflow-y-auto rounded-lg border border-slate-200 bg-white p-3 text-left shadow-lg"
            style={{
              top: position?.top ?? -1000,
              left: position?.left ?? -1000,
              width: position?.width ?? 340,
              visibility: position ? "visible" : "hidden",
            }}
          >
            <p className="text-base font-semibold text-slate-800">
              {general ? "Serviços com estimativas" : "Estimativas"}
            </p>

            {general ? (
              <div className="mt-2 space-y-2">
                {entries.map((entry, index) => {
                  const theme = getServiceTheme(
                    entry.option ?? { label: entry.label }
                  );
                  const Icon = theme.Icon;

                  return (
                    <div
                      key={`${entry.label}-${index}`}
                      className="flex items-center gap-2 text-base font-medium text-slate-700"
                    >
                      <Icon
                        size={18}
                        className={theme.iconClass}
                        aria-hidden="true"
                      />
                      <span>{entry.label}</span>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="mt-2 space-y-1.5">
                {entries.flatMap((entry, entryIndex) =>
                  entry.details.map((detail, detailIndex) => (
                    <p
                      key={`${entry.label ?? "estimate"}-${entryIndex}-${detailIndex}`}
                      className="text-base leading-snug text-slate-600"
                    >
                      <strong className="font-semibold text-slate-800">
                        {detail.label}:
                      </strong>{" "}
                      {detail.text}
                    </p>
                  ))
                )}
              </div>
            )}

            <button
              type="button"
              onClick={() => setIsOpen(false)}
              className="mt-3 w-full rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200"
            >
              Fechar
            </button>
          </div>,
          document.body
        )
      : null;

  return (
    <div className="inline-flex">
      <button
        ref={buttonRef}
        type="button"
        aria-label="Informação sobre estimativas"
        aria-expanded={isOpen}
        onClick={() => setIsOpen((current) => !current)}
        className="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1"
      >
        <Info size={19} strokeWidth={2} aria-hidden="true" />
      </button>

      {popup}
    </div>
  );
}

// Personaliza a informação apresentada ao passar o rato sobre o gráfico.
function AnnualChartTooltip({ active, payload, label }) {
  if (!active || !payload?.length) return null;

  const row = payload[0]?.payload;

  if (!row) return null;

  const estimated =
    Number(row.estimated_months_count) > 0 ||
    row.benchmark_estimated === true ||
    (row.estimated_services ?? []).length > 0;

  return (
    <div className="min-w-47.5 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="mb-2 text-base font-semibold text-slate-900">{label}</p>

      <div className="space-y-1.5 text-sm">
        <div className="flex items-center justify-between gap-4">
          <span className="text-slate-500">Gasto da habitação</span>
          <strong className="tabular-nums text-slate-800">
            {money.format(Number(row.user_total))}
          </strong>
        </div>

        <div className="flex items-center justify-between gap-4">
          <span className="text-slate-500">Benchmark médio</span>
          <strong className="tabular-nums text-slate-800">
            {money.format(Number(row.benchmark_total))}
          </strong>
        </div>
      </div>

      {estimated && (
        <p className="mt-2 border-t border-slate-100 pt-2 text-xs text-amber-700">
          Inclui valores estimados.
        </p>
      )}
    </div>
  );
}

// Obtém a cor das barras de acordo com o serviço selecionado.
function getUserBarColor(service) {
  return getServiceTheme({ value: service }).color;
}

// Gráfico com a evolução dos gastos e a comparação com o benchmark.
function AnnualCostsChart({ rows, service }) {
  // Ordena os anos e prepara os valores para o gráfico.
  const chartRows = [...rows]
  .sort((a, b) => Number(a.year) - Number(b.year))
  .map((row) => ({
    ...row,
    user_total: Number(row.user_total) || 0,
    benchmark_total: Number(row.benchmark_total) || 0,
  }));

  if (chartRows.length === 0) return null;

  const userBarColor = getUserBarColor(service);
  const latest = chartRows[chartRows.length - 1];
  const previous = chartRows.length > 1 ? chartRows[chartRows.length - 2] : null;

  // Calcula a variação percentual em relação ao ano anterior.
  const trendPercentage =
    previous && Number(previous.user_total) > 0
      ? ((latest.user_total - previous.user_total) / previous.user_total) * 100
      : null;

  // O último ano pode ainda estar incompleto (valores estimados ou projetados).
  const partialLatest =
    Number(latest.estimated_months_count) > 0 ||
    latest.benchmark_estimated === true;

  const increased = trendPercentage !== null && trendPercentage > 0;
  const decreased = trendPercentage !== null && trendPercentage < 0;

  return (
    <section
      className="border-t-4 px-5 py-5 sm:px-6"
      style={{ borderTopColor: HEADER_COLOR }}
    >
      {/* Cabeçalho do gráfico com indicador de evolução. */}
      <div
        className="relative -mx-5 -mt-5 mb-4 flex flex-col gap-2 overflow-hidden px-5 py-4 sm:-mx-6 sm:flex-row sm:items-start sm:justify-between sm:px-6"
        style={{ backgroundColor: HEADER_COLOR }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />
        <div className="pointer-events-none absolute right-1/4 top-4 h-12 w-12 rounded-full border border-white/10" />

        <div className="relative z-10">
          <h3 className="text-base font-semibold text-white">
            Evolução anual dos custos
          </h3>
          <p className="mt-1 text-sm text-white/75">
            Gastos da habitação comparados com o benchmark médio.
          </p>
        </div>

        {trendPercentage !== null && (
          <div
            className={`relative z-10 inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-semibold ${
              increased
                ? "bg-red-50 text-red-700"
                : decreased
                  ? "bg-emerald-50 text-emerald-700"
                  : "bg-slate-100 text-slate-600"
            }`}
          >
            {increased ? (
              <TrendingUp size={17} aria-hidden="true" />
            ) : decreased ? (
              <TrendingDown size={17} aria-hidden="true" />
            ) : null}

            {increased
              ? `Aumento de ${percent.format(trendPercentage)}% face a ${previous.year}`
              : decreased
                ? `Redução de ${percent.format(Math.abs(trendPercentage))}% face a ${previous.year}`
                : `Sem alteração (0%) face a ${previous.year}`}

            {partialLatest && (
              <span className="font-normal opacity-80">· ano incompleto</span>
            )}
          </div>
        )}
      </div>

      {/* Representação dos custos anuais em barras. */}
      <div className="h-65 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart
            data={chartRows}
            margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
            barGap={6}
            barCategoryGap="24%"
          >
            <CartesianGrid
              stroke="#e2e8f0"
              strokeDasharray="3 3"
              vertical={false}
            />
            <XAxis
              dataKey="year"
              tick={{ fill: "#64748b", fontSize: 14 }}
              tickLine={false}
              axisLine={{ stroke: "#e2e8f0" }}
            />
            <YAxis
              width={76}
              tickFormatter={(value) => `${compactMoney.format(value)} €`}
              tick={{ fill: "#64748b", fontSize: 12 }}
              tickLine={false}
              axisLine={false}
              allowDecimals={false}
            />
            <Tooltip
              content={<AnnualChartTooltip />}
              cursor={{ fill: "#f8fafc" }}
            />
            <Legend
              verticalAlign="bottom"
              height={36}
              iconType="circle"
              iconSize={9}
              formatter={(value) => (
                <span className="text-sm text-slate-600">{value}</span>
              )}
            />
            <Bar
              dataKey="user_total"
              name="Gasto da habitação"
              radius={[5, 5, 0, 0]}
              maxBarSize={42}
            >
              {chartRows.map((row) => (
                <Cell key={`user-${row.year}`} fill={userBarColor} />
              ))}
            </Bar>
            <Bar
              dataKey="benchmark_total"
              name="Benchmark médio"
              fill="#cbd5e1"
              radius={[5, 5, 0, 0]}
              maxBarSize={42}
            />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </section>
  );
}

// Prepara os valores e a informação de estimativas de cada ano.
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

  const estimatedServices = isGeneralView ? row.estimated_services ?? [] : [];

  const hasRowEstimate = isGeneralView
    ? estimatedServices.length > 0
    : userEstimated || benchmarkEstimated;

  const estimateEntries = isGeneralView
    ? estimatedServices.map((detail) => {
        const option =
          options.find(
            (item) =>
              String(item.value) === String(detail.consumption_type_id)
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

// Tabela principal de comparação anual por habitação e serviço.
export default function AnnualBenchmarkTable({ propertyId, refreshKey }) {
  // Guarda o filtro selecionado para cada habitação.
  const [serviceSelection, setServiceSelection] = useState({
    propertyId: null,
    value: "all",
  });

  // Permite repetir manualmente um pedido que falhou.
  const [retryCount, setRetryCount] = useState(0);

  const requestedService =
    serviceSelection.propertyId === propertyId ? serviceSelection.value : "all";

  // Resultado do último pedido, identificado pela habitação e atualização.
  const [fetched, setFetched] = useState({
    key: null,
    propertyId: null,
    data: null,
    error: null,
  });

  const requestKey = `${propertyId ?? ""}-${refreshKey ?? 0}-${retryCount}`;
  const current = propertyId && fetched.key === requestKey ? fetched : null;

  // Durante uma atualização da mesma habitação, mantém os dados anteriores visíveis.
  const display =
    current ??
    (propertyId && fetched.propertyId === propertyId ? fetched : null);

  // Valores derivados: sem setState síncrono dentro do efeito.
  const result = display?.data ?? null;
  const error = current?.error ?? null;
  const loading = Boolean(propertyId) && current === null;

  // Carrega os dados anuais quando a habitação ou a atualização muda.
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
        // Mantém o erro técnico na consola e apresenta apenas uma mensagem segura.
        console.error("[AnnualBenchmarkTable] Erro ao carregar o benchmark:", err);

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

  // Prepara as opções de serviço e verifica a disponibilidade do filtro.
  const options = result?.service_options ?? [];
  const hasGeneralOption = options.some(isGeneralService);

  const requestedServiceIsAvailable = options.some(
    (option) => String(option.value) === String(requestedService)
  );

  // Seleciona um serviço válido caso a opção anterior já não esteja disponível.
  const activeService =
    result && !requestedServiceIsAvailable
      ? hasGeneralOption
        ? "all"
        : options.find((option) => !isGeneralService(option))?.value ?? "all"
      : requestedService;

  const isGeneralView = isGeneralService({ value: activeService });

  // Obtém os dados gerais ou os dados específicos do serviço selecionado.
  const rows = isGeneralView
    ? result?.data ?? []
    : result?.services?.[String(activeService)]?.data ?? [];

  // Atualiza o filtro sem alterar os dados recebidos da API.
  function selectService(value) {
    setServiceSelection({
      propertyId,
      value,
    });
  }

  // Repete o pedido à API após uma falha genérica.
  function retryRequest() {
    setRetryCount((count) => count + 1);
  }

  // Oculta a secção só quando não há dados nem filtros para alternar.
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
        {/* Elementos decorativos do cabeçalho. */}
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

          {/* Botões de filtro alinhados à direita no cabeçalho. */}
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

      {/* Estado de erro apresentado sem expor mensagens SQL ou detalhes internos. */}
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

      {/* Mensagem apresentada quando não existe uma habitação selecionada. */}
      {!propertyId && !error && (
        <div className="px-5 py-12 text-center text-base text-slate-500 sm:px-6">
          Seleciona uma casa para ver a comparação anual.
        </div>
      )}

      {/* Indicador de carregamento dos dados. */}
      {propertyId && loading && rows.length === 0 && (
        <div className="flex items-center justify-center gap-2 px-5 py-12 text-base text-slate-500 sm:px-6">
          <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-slate-600" />
          A carregar comparação anual...
        </div>
      )}

      {/* Estado vazio do serviço selecionado (os filtros continuam disponíveis). */}
      {propertyId && !loading && !error && rows.length === 0 && (
        <div className="px-5 py-12 text-center text-base text-slate-500 sm:px-6">
          Ainda não existem dados anuais para este serviço.
        </div>
      )}

      {/* Tabela e gráfico apresentados quando existem dados. */}
      {rows.length > 0 && (
        <>
          {/* Cartões de comparação para telemóveis e tablets. */}
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
                      <span className="rounded-lg bg-[#1C3557]/10 px-3 py-1.5 text-base font-bold tabular-nums text-[#1C3557]">
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
                      <p className="mt-1 break-words text-base font-bold tabular-nums text-slate-900">
                        {money.format(Number(row.user_total))}
                      </p>
                    </div>

                    <div className="min-w-0 rounded-lg bg-slate-50 p-3">
                      <p className="text-xs font-semibold uppercase leading-4 tracking-wide text-slate-500">
                        Benchmark médio
                      </p>
                      <p className="mt-1 break-words text-base font-bold tabular-nums text-slate-800">
                        {money.format(Number(row.benchmark_total))}
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
                        {signedMoney.format(difference)}
                      </span>
                      {row.difference_percentage != null && (
                        <span
                          className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-semibold tabular-nums ${differencePillStyle}`}
                        >
                          {signedPercent.format(
                            Number(row.difference_percentage)
                          )}
                          %
                        </span>
                      )}
                    </div>
                  </div>
                </article>
              );
            })}
          </div>

          {/* Tabela tradicional para ecrãs maiores. */}
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
                  <th className="px-4 py-4 text-right">Gasto da habitação (€)</th>
                  <th className="px-4 py-4 text-right">Benchmark médio (€)</th>
                  <th className="px-5 py-4 text-right sm:px-6">Diferença (€)</th>
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
                        {money.format(Number(row.user_total))}
                      </td>

                      <td className="px-4 py-5 text-right font-semibold tabular-nums text-slate-800">
                        {money.format(Number(row.benchmark_total))}
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
                          {signedMoney.format(difference)}
                        </span>
                        {row.difference_percentage != null && (
                          <div className="mt-1.5">
                            <span
                              className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-semibold tabular-nums ${differencePillStyle}`}
                            >
                              {signedPercent.format(
                                Number(row.difference_percentage)
                              )}
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

          {/* Notas explicativas e aviso sobre os valores apresentados. */}
          <footer className="border-t border-slate-100 px-5 py-4 sm:px-6">
            <p className="mb-3 text-sm leading-relaxed text-slate-500">
              Uma diferença negativa significa que a habitação gastou menos do
              que o benchmark. Uma diferença positiva significa que gastou
              mais.
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
                Os benchmarks são médias de habitações semelhantes e não
                valores exatos. Os anos ainda incompletos podem incluir
                projeções anuais, tanto nos consumos como nos benchmarks, pelo
                que os totais podem diferir dos custos efetivamente registados
                nas faturas.
              </p>
            </div>
          </footer>
        </>
      )}
    </section>
  );
}