import { useEffect, useMemo, useRef, useState } from "react";
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
  Droplets,
  FileText,
  Flame,
  Info,
  TrendingDown,
  TrendingUp,
  Zap,
} from "lucide-react";
import axiosClient from "../../services/api/axiosClient";

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

// Obtém uma mensagem de erro adequada a partir da resposta da API.
function getErrorMessage(err) {
  const errors = err?.response?.data?.errors;

  if (errors && typeof errors === "object") {
    const messages = [...new Set(Object.values(errors).flat())];

    if (messages.length > 0) return messages.join(" ");
  }

  return (
    err?.response?.data?.message ||
    "Não foi possível carregar a comparação anual."
  );
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
  const [clickedOpen, setClickedOpen] = useState(false);
  const [hovered, setHovered] = useState(false);
  const [position, setPosition] = useState(null);

  const buttonRef = useRef(null);
  const popupRef = useRef(null);

  const isOpen = clickedOpen || hovered;

  // Atualiza a posição da janela quando abre ou quando a página se desloca.
  useEffect(() => {
    if (!isOpen) {
      setPosition(null);
      return undefined;
    }

    const updatePosition = () => {
      const anchor = buttonRef.current?.getBoundingClientRect();

      if (!anchor) return;

      const popupWidth = Math.min(340, window.innerWidth - 24);
      const popupHeight = popupRef.current?.offsetHeight ?? 100;

      const left = Math.max(
        12,
        Math.min(anchor.left, window.innerWidth - popupWidth - 12)
      );

      const below = anchor.bottom + 6;
      const top =
        below + popupHeight <= window.innerHeight - 12
          ? below
          : Math.max(12, anchor.top - popupHeight - 6);

      setPosition({ top, left, width: popupWidth });
    };

    updatePosition();

    window.addEventListener("resize", updatePosition);
    window.addEventListener("scroll", updatePosition, true);

    return () => {
      window.removeEventListener("resize", updatePosition);
      window.removeEventListener("scroll", updatePosition, true);
    };
  }, [isOpen]);

  if (!entries?.length) return null;

  // Cria a janela fora da tabela para evitar problemas de posicionamento.
  const popup =
    isOpen && typeof document !== "undefined"
      ? createPortal(
          <div
            ref={popupRef}
            role="tooltip"
            className="fixed z-[1000] rounded-lg border border-slate-200 bg-white p-3 text-left shadow-lg"
            style={{
              top: position?.top ?? -1000,
              left: position?.left ?? -1000,
              width: position?.width ?? 340,
              visibility: position ? "visible" : "hidden",
            }}
          >
            <p className="text-sm font-semibold text-slate-800">
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
                      className="flex items-center gap-2 text-sm font-medium text-slate-700"
                    >
                      <Icon
                        size={16}
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
                      className="whitespace-nowrap text-sm leading-snug text-slate-600"
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
          </div>,
          document.body
        )
      : null;

  return (
    <div
      className="inline-flex"
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
    >
      <button
        ref={buttonRef}
        type="button"
        aria-label="Informação sobre estimativas"
        aria-expanded={isOpen}
        onClick={() => setClickedOpen((current) => !current)}
        onKeyDown={(event) => {
          if (event.key === "Escape") {
            setClickedOpen(false);
            setHovered(false);
          }
        }}
        className="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1"
      >
        <Info size={15} strokeWidth={2} aria-hidden="true" />
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
    <div className="min-w-[190px] rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="mb-2 text-sm font-semibold text-slate-900">{label}</p>

      <div className="space-y-1.5 text-xs">
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
        <p className="mt-2 border-t border-slate-100 pt-2 text-[10px] text-amber-700">
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
    .map((row, index, allRows) => {
      const previous = index > 0 ? allRows[index - 1] : null;

      return {
        ...row,
        user_total: Number(row.user_total) || 0,
        benchmark_total: Number(row.benchmark_total) || 0,
        user_increased:
          previous !== null &&
          Number(row.user_total) > Number(previous.user_total),
      };
    });

  if (chartRows.length === 0) return null;

  const userBarColor = getUserBarColor(service);
  const latest = chartRows[chartRows.length - 1];
  const previous = chartRows.length > 1
    ? chartRows[chartRows.length - 2]
    : null;

  // Calcula a variação percentual em relação ao ano anterior.
  const trendPercentage =
    previous && Number(previous.user_total) > 0
      ? ((latest.user_total - previous.user_total) / previous.user_total) * 100
      : null;

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
          <h3 className="text-sm font-semibold text-white">
            Evolução anual dos custos
          </h3>
          <p className="mt-1 text-xs text-white/75">
            Gastos da habitação comparados com o benchmark médio.
          </p>
        </div>

        {trendPercentage !== null && (
          <div
            className={`relative z-10 inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${
              increased
                ? "bg-red-50 text-red-700"
                : decreased
                  ? "bg-emerald-50 text-emerald-700"
                  : "bg-slate-100 text-slate-600"
            }`}
          >
            {increased ? (
              <TrendingUp size={14} aria-hidden="true" />
            ) : decreased ? (
              <TrendingDown size={14} aria-hidden="true" />
            ) : null}
            {increased
              ? `Aumento de ${signedPercent.format(trendPercentage)}% face a ${previous.year}`
              : decreased
                ? `Redução de ${percent.format(Math.abs(trendPercentage))}% face a ${previous.year}`
                : `Sem alteração (0%) face a ${previous.year}`}
          </div>
        )}
      </div>

      {/* Representação dos custos anuais em barras. */}
      <div className="h-[260px] w-full">
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
              tick={{ fill: "#64748b", fontSize: 11 }}
              tickLine={false}
              axisLine={{ stroke: "#e2e8f0" }}
            />
            <YAxis
              width={66}
              tickFormatter={(value) => `${compactMoney.format(value)} €`}
              tick={{ fill: "#64748b", fontSize: 10 }}
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
              height={30}
              iconType="circle"
              iconSize={8}
              formatter={(value) => (
                <span className="text-xs text-slate-600">{value}</span>
              )}
            />
            <Bar
              dataKey="user_total"
              name="Gasto da habitação"
              radius={[5, 5, 0, 0]}
              maxBarSize={42}
            >
              {chartRows.map((row) => (
                <Cell
                  key={`user-${row.year}`}
                  fill={userBarColor}
                />
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

// Tabela principal de comparação anual por habitação e serviço.
export default function AnnualBenchmarkTable({ propertyId, refreshKey }) {
  // Guarda o filtro selecionado para cada habitação.
  const [serviceSelection, setServiceSelection] = useState({
    propertyId: null,
    value: "all",
  });

  const requestedService =
    serviceSelection.propertyId === propertyId
      ? serviceSelection.value
      : "all";

  // Estado dos dados, carregamento e erros da API.
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  // Carrega os dados anuais sempre que a habitação ou a chave de atualização muda.
  useEffect(() => {
    if (!propertyId) {
      setResult(null);
      setLoading(false);
      setError("");
      return undefined;
    }

    // Evita atualizar o estado se o pedido anterior já não for válido.
    let cancelled = false;

    setResult(null);
    setLoading(true);
    setError("");

    axiosClient
      .get("/api/v1/benchmarks/annual/series", {
        params: {
          property_id: propertyId,
          service: "all",
        },
      })
      .then((response) => {
        if (!cancelled) {
          setResult(response.data);
        }
      })
      .catch((err) => {
        if (!cancelled) {
          setResult(null);
          setError(getErrorMessage(err));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [propertyId, refreshKey]);

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

  // Oculta a secção quando não existem dados para apresentar.
  if (propertyId && !loading && !error && rows.length === 0) {
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
            <h2 className="text-lg font-semibold tracking-tight text-white">
              Comparação anual com benchmarks
            </h2>

            <p className="mt-1 text-sm text-white/75">
              Comparação dos gastos anuais da habitação com a média anual de
              habitações similares.
            </p>
          </div>

          {/* Botões de filtro alinhados à direita no cabeçalho. */}
          {options.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {options.map((option) => {
                const selected =
                  String(activeService) === String(option.value);
                const theme = getServiceTheme(option);
                const Icon = theme.Icon;

                return (
                  <button
                    key={option.value}
                    type="button"
                    onClick={() => selectService(option.value)}
                    aria-pressed={selected}
                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                      selected
                        ? theme.selectedClass
                        : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                    }`}
                    style={
                      selected
                        ? undefined
                        : { color: "#FFFFFF" }
                    }
                  >
                    <Icon
                      size={16}
                      strokeWidth={2}
                      aria-hidden="true"
                    />
                    <span>{option.label}</span>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </header>

      {/* Mensagem de erro devolvida pela API. */}
      {error && (
        <div className="px-5 pt-4 sm:px-6">
          <p className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            {error}
          </p>
        </div>
      )}

      {/* Mensagem apresentada quando não existe uma habitação selecionada. */}
      {!propertyId && !error && (
        <div className="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">
          Seleciona uma casa para ver a comparação anual.
        </div>
      )}

      {/* Indicador de carregamento dos dados. */}
      {propertyId && loading && rows.length === 0 && (
        <div className="flex items-center justify-center gap-2 px-5 py-12 text-sm text-slate-500 sm:px-6">
          <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-slate-600" />
          A carregar comparação anual...
        </div>
      )}

      {/* Tabela e gráfico apresentados quando existem dados. */}
      {rows.length > 0 && (
        <>
          {/* Tabela de comparação dos valores anuais. */}
          <div className="w-full overflow-x-auto">
            <table className="w-full min-w-[620px] border-collapse text-sm">
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
                <tr className="text-[11px] font-semibold uppercase tracking-wide text-white">
                  <th className="px-5 py-4 text-left sm:px-6">Ano</th>
                  <th className="px-4 py-4 text-right">
                    Gasto utilizador (€)
                  </th>
                  <th className="px-4 py-4 text-right">
                    Benchmark médio (€)
                  </th>
                  <th className="px-5 py-4 text-right sm:px-6">
                    Diferença (€)
                  </th>
                </tr>
              </thead>

              <tbody>
                {rows.map((row) => {
                  // Identifica os valores estimados e calcula a diferença anual.
                  const userEstimated =
                    Number(row.estimated_months_count) > 0;
                  const benchmarkEstimated =
                    row.benchmark_estimated === true;
                  const difference = Number(row.difference ?? 0);

                  // Define as cores da diferença consoante o resultado.
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

                  // Obtém os serviços com estimativas na visão geral.
                  const estimatedServices = isGeneralView
                    ? row.estimated_services ?? []
                    : [];

                  const hasRowEstimate = isGeneralView
                    ? estimatedServices.length > 0
                    : userEstimated || benchmarkEstimated;

                  // Prepara os detalhes que serão apresentados no ícone de informação.
                  const estimateEntries = isGeneralView
                    ? estimatedServices.map((detail) => {
                        const option = options.find(
                          (item) =>
                            String(item.value) ===
                            String(detail.consumption_type_id)
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
                                      Number(
                                        row.benchmark_estimated_months_count
                                      ) > 0
                                        ? ` (${row.benchmark_estimated_months_count} meses)`
                                        : ""
                                    }`,
                                  },
                                ]
                              : []),
                          ],
                        },
                      ];

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
                          <p className="mt-1 text-[11px] font-normal text-slate-400">
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
                              className={`inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums ${differencePillStyle}`}
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
            <p className="mb-3 text-[11px] leading-relaxed text-slate-500">
              Uma diferença negativa significa que a habitação gastou menos
              do que o benchmark. Uma diferença positiva significa que
              gastou mais.
            </p>

            <div className="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3">
              <Info
                size={16}
                className="mt-0.5 shrink-0 text-slate-500"
                aria-hidden="true"
              />

              <p className="text-[11px] leading-relaxed text-slate-600">
                <span className="font-semibold text-slate-700">
                  Valores indicativos.
                </span>{" "}
                Os benchmarks são médias de habitações semelhantes e não
                valores exatos. Os anos ainda incompletos podem incluir
                projeções anuais, tanto nos consumos como nos benchmarks,
                pelo que os totais podem diferir dos custos efetivamente
                registados nas faturas.
              </p>
            </div>
          </footer>
        </>
      )}
    </section>
  );
}