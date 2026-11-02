import React, { useEffect, useMemo, useState } from "react";
import axiosClient from "../../services/api/axiosClient";
import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
} from "recharts";

// Configuração dos tipos de consumo e das respetivas cores.
const UTILITIES = {
  1: {
    name: "Eletricidade",
    color: "#1C3557",
    colors: [
      "#1C3557",
      "#344D6C",
      "#506885",
      "#6C839F",
      "#95A5B8",
      "#778A9F",
    ],
  },
  2: {
    name: "Água",
    color: "#0F766E",
    colors: [
      "#0F766E",
      "#278881",
      "#439B94",
      "#76B2AE",
      "#A0CBC7",
    ],
  },
  3: {
    name: "Gás",
    color: "#EA580C",
    colors: [
      "#EA580C",
      "#F2722B",
      "#F89659",
      "#FBC4A0",
      "#FCE4D6",
    ],
  },
};

const CREDIT_COLOR = "#059669";

// Filtros disponíveis para o gráfico.
const FILTERS = [
  { id: "all", label: "Geral" },
  { id: "1", label: "Eletricidade" },
  { id: "2", label: "Água" },
  { id: "3", label: "Gás" },
];

// Tradução das categorias dos itens das faturas.
const CATEGORY_LABELS = {
  consumption: "Consumo",
  fixed_fee: "Tarifa fixa",
  tax: "Imposto / taxa",
  other: "Outro",
};

// Formatação dos valores monetários e das quantidades.
const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

const number = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 3,
});

// Converte valores recebidos da API para números válidos.
function asNumber(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value)
    .trim()
    .replace(/\s/g, "")
    .replace(/[€]/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);
  return Number.isFinite(result) ? result : 0;
}

// Obtém a lista de faturas de diferentes formatos de resposta da API.
function responseArray(response) {
  const body = response?.data ?? response;

  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;
  if (Array.isArray(body?.data?.data)) return body.data.data;

  return [];
}

// Identifica o tipo de consumo através do ID ou do nome.
function getTypeId(consumption) {
  const raw =
    consumption?.consumption_type_id ??
    consumption?.consumptionTypeId ??
    consumption?.consumption_type?.id ??
    consumption?.consumptionType?.id ??
    consumption?.type_id ??
    consumption?.type?.id;

  const id = Number(raw);

  if (UTILITIES[id]) return id;

  const name = String(
    consumption?.consumption_type?.name ??
      consumption?.consumptionType?.name ??
      consumption?.type?.name ??
      consumption?.name ??
      ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;

  if (
    name.includes("água") ||
    name.includes("agua") ||
    name.includes("water")
  ) {
    return 2;
  }

  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

// Procura os itens detalhados associados a cada consumo.
function getItems(consumption) {
  const possible = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return possible.find(Array.isArray) ?? [];
}

// Obtém o custo de uma linha individual da fatura.
function itemCost(item) {
  return asNumber(
    item?.cost ??
      item?.total_cost ??
      item?.totalCost ??
      item?.amount_cost ??
      item?.price ??
      0
  );
}

// Obtém a quantidade de um item.
function itemAmount(item) {
  return asNumber(
    item?.amount ??
      item?.quantity ??
      item?.consumption ??
      0
  );
}

// Obtém a unidade de medida de um item.
function itemUnit(item) {
  return String(
    item?.unit ??
      item?.measurement_unit ??
      item?.unit_of_measure ??
      ""
  ).trim();
}

// Obtém o nome ou descrição de um item.
function itemName(item, index) {
  return String(
    item?.name ??
      item?.description ??
      item?.label ??
      item?.concept ??
      item?.category ??
      `Item ${index + 1}`
  ).trim();
}

// Constrói a descrição apresentada nos detalhes do gráfico.
function itemDescription(item) {
  const description = String(
    item?.description ??
      item?.details ??
      item?.notes ??
      item?.tariff ??
      item?.rate_name ??
      ""
  ).trim();

  const rawCategory = String(item?.category ?? "").trim();
  const category = CATEGORY_LABELS[rawCategory] ?? rawCategory;
  const amount = itemAmount(item);
  const unit = itemUnit(item);

  const quantityText =
    amount !== 0
      ? `${number.format(amount)}${unit ? ` ${unit}` : ""}`
      : "";

  const name = String(item?.name ?? "").trim();

  return [
    description && description !== name ? description : "",
    category && category !== name ? category : "",
    quantityText,
  ]
    .filter(Boolean)
    .filter((part, index, array) => array.indexOf(part) === index)
    .join(" · ");
}

// Organiza os itens das faturas para serem apresentados no gráfico.
function normalizeInvoices(invoices) {
  const result = [];
  const itemIndexes = { 1: 0, 2: 0, 3: 0 };

  invoices.forEach((invoice, invoiceIndex) => {
    const consumptions =
      invoice?.consumptions ??
      invoice?.utilities ??
      [];

    if (!Array.isArray(consumptions)) return;

    consumptions.forEach((consumption, consumptionIndex) => {
      const typeId = getTypeId(consumption);
      if (!typeId) return;

      const utility = UTILITIES[typeId];
      const items = getItems(consumption);

      const invoiceLabel = String(
        invoice?.invoice_number ??
          invoice?.number ??
          invoice?.reference ??
          `Fatura ${invoiceIndex + 1}`
      );

      // Se existem itens detalhados, cria uma fatia para cada item.
      if (items.length > 0) {
        items.forEach((item, itemIndex) => {
          const cost = itemCost(item);
          const colorIndex = itemIndexes[typeId]++;

          result.push({
            id: [
              invoice?.id ?? invoiceIndex,
              consumption?.id ?? consumptionIndex,
              item?.id ?? itemIndex,
            ].join("-"),
            typeId,
            utility: utility.name,
            color:
              cost < 0
                ? CREDIT_COLOR
                : utility.colors[
                    colorIndex % utility.colors.length
                  ],
            name: itemName(item, itemIndex),
            description: itemDescription(item),
            category: item?.category ?? "",
            categoryLabel:
              CATEGORY_LABELS[item?.category] ??
              item?.category ??
              "",
            amount: itemAmount(item),
            unit: itemUnit(item),
            cost,
            sliceValue: Math.abs(cost),
            isCredit: cost < 0,
            invoiceLabel,
          });
        });

        return;
      }

      // Se não existem itens, utiliza o custo total do consumo.
      const cost = asNumber(
        consumption?.cost ??
          consumption?.total_cost ??
          consumption?.totalCost ??
          0
      );

      const amount = asNumber(
        consumption?.amount ?? consumption?.quantity
      );

      const unit = itemUnit(consumption);

      result.push({
        id: `${invoice?.id ?? invoiceIndex}-${consumptionIndex}-total`,
        typeId,
        utility: utility.name,
        color: cost < 0 ? CREDIT_COLOR : utility.color,
        name: `${utility.name} — total`,
        description:
          amount !== 0
            ? `${number.format(amount)}${
                unit ? ` ${unit}` : ""
              } · Total do consumo`
            : "Total do consumo",
        category: "",
        categoryLabel: "",
        amount,
        unit,
        cost,
        sliceValue: Math.abs(cost),
        isCredit: cost < 0,
        invoiceLabel,
      });
    });
  });

  return result;
}

// Apresenta os detalhes do item selecionado no gráfico.
function ChartTooltip({ active, payload }) {
  if (!active || !payload?.length) return null;

  const item = payload[0]?.payload;
  if (!item) return null;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="font-semibold text-slate-800">
        {item.name}
      </p>

      <p className="mt-1 text-sm text-slate-500">
        {item.utility}
      </p>

      {item.description && (
        <p className="mt-2 max-w-64 text-xs text-slate-500">
          {item.description}
        </p>
      )}

      <p
        className={`mt-2 font-semibold ${
          item.isCredit
            ? "text-emerald-700"
            : "text-slate-900"
        }`}
      >
        {money.format(item.cost)}
        {item.isCredit ? " · Crédito" : ""}
      </p>

      {item.invoiceLabel && (
        <p className="mt-1 text-xs text-slate-400">
          {item.invoiceLabel}
        </p>
      )}
    </div>
  );
}

export default function DetailedConsumptionChart({
  invoices: invoicesProp,
  propertyId,
  refreshKey = 0,
}) {
  // Guarda as faturas, o estado de carregamento e os erros.
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Guarda o filtro e o item atualmente selecionado.
  const [filter, setFilter] = useState("all");
  const [activeItemId, setActiveItemId] = useState(null);

  // Carrega as faturas ou utiliza os dados recebidos pela página.
  useEffect(() => {
    let cancelled = false;

    async function load() {
      if (Array.isArray(invoicesProp)) {
        setInvoices(invoicesProp);
        setLoading(false);
        setError("");
        return;
      }

      setLoading(true);
      setError("");

      try {
        const response = await axiosClient.get(
          "/api/v1/invoices",
          {
            params: propertyId
              ? { property_id: propertyId }
              : {},
          }
        );

        if (!cancelled) {
          setInvoices(responseArray(response));
        }
      } catch (err) {
        if (!cancelled) {
          setInvoices([]);
          setError(
            err?.response?.data?.message ??
              "Não foi possível carregar as faturas."
          );
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    load();

    // Evita atualizações depois de desmontar o componente.
    return () => {
      cancelled = true;
    };
  }, [invoicesProp, propertyId, refreshKey]);

  // Prepara os itens e filtra os resultados selecionados.
  const allItems = useMemo(
    () => normalizeInvoices(invoices),
    [invoices]
  );

  const visibleItems = useMemo(
    () =>
      filter === "all"
        ? allItems
        : allItems.filter(
            (item) => String(item.typeId) === filter
          ),
    [allItems, filter]
  );

  // Calcula o total do filtro selecionado.
  const total = visibleItems.reduce(
    (sum, item) => sum + item.cost,
    0
  );

  // Prepara os valores que serão desenhados no gráfico.
  const chartItems = visibleItems.filter(
    (item) => item.sliceValue > 0
  );

  // Agrupa os itens por tipo de consumo.
  const groups = [1, 2, 3]
    .filter(
      (id) => filter === "all" || filter === String(id)
    )
    .map((id) => ({
      id,
      ...UTILITIES[id],
      items: visibleItems.filter(
        (item) => item.typeId === id
      ),
    }))
    .filter((group) => group.items.length > 0);

  // Obtém o item selecionado para apresentar no centro do gráfico.
  const selectedItem = visibleItems.find(
    (item) => item.id === activeItemId
  );

  // Limpa a seleção caso o item deixe de existir nos dados visíveis.
  useEffect(() => {
    if (
      activeItemId &&
      !visibleItems.some(
        (item) => item.id === activeItemId
      )
    ) {
      setActiveItemId(null);
    }
  }, [activeItemId, visibleItems]);

  // Altera o filtro e limpa o item selecionado.
  function selectFilter(id) {
    setFilter(id);
    setActiveItemId(null);
  }

  // Apresenta o estado de carregamento.
  if (loading) {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <p className="text-sm text-slate-500">
          A carregar consumos...
        </p>
      </section>
    );
  }

  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      {/* Cabeçalho e filtros do gráfico. */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
          <h2 className="text-lg font-semibold text-slate-900">
            Detalhe dos consumos
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            Custos detalhados por serviço e por item de fatura.
          </p>
        </div>

        <div className="flex flex-wrap gap-2">
          {FILTERS.map((option) => (
            <button
              key={option.id}
              type="button"
              onClick={() => selectFilter(option.id)}
              className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                filter === option.id
                  ? "border-slate-900 bg-slate-900 text-white"
                  : "border-slate-200 text-slate-600 hover:bg-slate-50"
              }`}
            >
              {option.label}
            </button>
          ))}
        </div>
      </div>

      {/* Mensagem de erro da API. */}
      {error && (
        <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
          {error}
        </p>
      )}

      {!visibleItems.length ? (
        // Mensagem apresentada quando não existem itens.
        <div className="py-12 text-center">
          <p className="font-medium text-slate-700">
            Ainda não existem consumos para apresentar.
          </p>

          <p className="mt-1 text-sm text-slate-500">
            Guarda uma fatura para consultar os seus detalhes.
          </p>
        </div>
      ) : (
        <>
          <div className="mt-6 grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(280px,0.85fr)_minmax(0,1.15fr)]">
            {/* Gráfico em anel e resumo dos totais. */}
            <div className="min-w-0">
              <div className="relative h-[320px]">
                {chartItems.length > 0 ? (
                  <>
                    <ResponsiveContainer
                      width="100%"
                      height="100%"
                    >
                      <PieChart>
                        <Pie
                          data={chartItems}
                          dataKey="sliceValue"
                          nameKey="name"
                          cx="50%"
                          cy="50%"
                          innerRadius="59%"
                          outerRadius="82%"
                          paddingAngle={2}
                          stroke="#fff"
                          strokeWidth={2}
                          isAnimationActive={false}
                        >
                          {chartItems.map((item) => (
                            <Cell
                              key={item.id}
                              fill={item.color}
                              stroke="#ffffff"
                              strokeWidth={2}
                            />
                          ))}
                        </Pie>

                        {/* Tooltip apresentado por cima do texto central. */}
                        <Tooltip
                          content={<ChartTooltip />}
                          wrapperStyle={{
                            zIndex: 9999,
                            pointerEvents: "none",
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>

                    {/* Texto central do gráfico. */}
                    <div
                      className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-8 text-center"
                      style={{
                        zIndex: 1,
                      }}
                    >
                      {selectedItem ? (
                        <>
                          <span className="max-w-full text-xs text-slate-500">
                            {selectedItem.name}
                          </span>

                          <strong
                            className={`mt-1 text-xl ${
                              selectedItem.isCredit
                                ? "text-emerald-700"
                                : "text-slate-900"
                            }`}
                          >
                            {money.format(selectedItem.cost)}
                          </strong>

                          <span className="mt-1 text-xs text-slate-500">
                            {selectedItem.utility}
                          </span>
                        </>
                      ) : (
                        <>
                          <span className="text-sm text-slate-500">
                            Total líquido
                          </span>

                          <strong className="mt-1 text-2xl text-slate-900">
                            {money.format(total)}
                          </strong>

                          <span className="mt-1 text-xs text-slate-500">
                            {visibleItems.length}{" "}
                            {visibleItems.length === 1
                              ? "item"
                              : "itens"}
                          </span>
                        </>
                      )}
                    </div>
                  </>
                ) : (
                  <div className="flex h-full items-center justify-center text-center text-sm text-slate-500">
                    Não existem custos diferentes de zero para desenhar o gráfico.
                  </div>
                )}
              </div>

              {/* Resumo dos totais por serviço. */}
              <div className="mt-3 space-y-2">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0
                  );

                  return (
                    <div
                      key={group.id}
                      className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <span className="flex items-center gap-2 text-sm text-slate-600">
                        <span
                          className="h-2.5 w-2.5 rounded-full"
                          style={{
                            backgroundColor: group.color,
                          }}
                        />

                        {group.name}
                      </span>

                      <strong
                        className={`text-sm ${
                          groupTotal < 0
                            ? "text-emerald-700"
                            : "text-slate-800"
                        }`}
                      >
                        {money.format(groupTotal)}
                      </strong>
                    </div>
                  );
                })}
              </div>

              <p className="mt-3 text-xs leading-5 text-slate-400">
                Cada fatia representa uma linha de custo. Os créditos
                são apresentados a verde e dimensionados pelo valor
                absoluto, para manter o anel proporcional.
              </p>
            </div>

            {/* Lista detalhada dos itens com scroll. */}
            <div className="min-w-0">
              <div className="max-h-[420px] space-y-3 overflow-y-auto overscroll-contain pr-2 [scrollbar-gutter:stable]">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0
                  );

                  return (
                    <div key={group.id}>
                      {/* Cabeçalho do grupo de consumo. */}
                      <div className="sticky top-0 z-10 mb-1 flex items-center justify-between gap-2 border-b border-slate-200 bg-white/95 pb-1.5 pt-1 backdrop-blur-sm">
                        <div className="flex min-w-0 items-center gap-1.5">
                          <span
                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                            style={{
                              backgroundColor: group.color,
                            }}
                          />

                          <h3 className="truncate text-sm font-bold text-slate-900">
                            {group.name}
                          </h3>
                        </div>

                        <strong
                          className={`shrink-0 text-sm font-bold ${
                            groupTotal < 0
                              ? "text-emerald-700"
                              : "text-slate-900"
                          }`}
                        >
                          {money.format(groupTotal)}
                        </strong>
                      </div>

                      {/* Linhas individuais da fatura. */}
                      <div className="space-y-0.5">
                        {group.items.map((item) => {
                          const active = activeItemId === item.id;
                          const dimmed =
                            activeItemId !== null && !active;

                          const metadata = [
                            item.categoryLabel,
                            item.description,
                            item.invoiceLabel
                              ? `N.º ${item.invoiceLabel}`
                              : "",
                          ]
                            .map((value) =>
                              String(value ?? "").trim()
                            )
                            .filter(Boolean)
                            .filter(
                              (value, index, array) =>
                                array.indexOf(value) === index
                            )
                            .join(" · ");

                          return (
                            <div
                              key={item.id}
                              onMouseEnter={() =>
                                setActiveItemId(item.id)
                              }
                              onMouseLeave={() =>
                                setActiveItemId(null)
                              }
                              className={`flex items-start gap-2 rounded-md px-2 py-1 transition ${
                                active ? "bg-slate-50" : ""
                              }`}
                              style={{
                                opacity: dimmed ? 0.35 : 1,
                              }}
                            >
                              <span
                                className="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                style={{
                                  backgroundColor: item.color,
                                }}
                              />

                              <div className="min-w-0 flex-1">
                                <p
                                  className="truncate text-sm font-semibold leading-5 text-slate-800"
                                  title={item.name}
                                >
                                  {item.name}
                                </p>

                                {metadata && (
                                  <p
                                    className="mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-500"
                                    title={metadata}
                                  >
                                    {metadata}
                                  </p>
                                )}

                                {item.isCredit && (
                                  <span className="mt-0.5 inline-block rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold leading-4 text-emerald-700">
                                    Crédito / autoconsumo
                                  </span>
                                )}
                              </div>

                              <strong
                                className={`shrink-0 whitespace-nowrap pt-0.5 text-sm font-semibold ${
                                  item.isCredit
                                    ? "text-emerald-700"
                                    : "text-slate-900"
                                }`}
                              >
                                {money.format(item.cost)}
                              </strong>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Total final do filtro selecionado. */}
          <div className="mt-5 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <span className="text-sm text-slate-500">
              Total líquido do filtro selecionado
            </span>

            <strong
              className={`text-lg ${
                total < 0
                  ? "text-emerald-700"
                  : "text-slate-900"
              }`}
            >
              {money.format(total)}
            </strong>
          </div>
        </>
      )}
    </section>
  );
}
