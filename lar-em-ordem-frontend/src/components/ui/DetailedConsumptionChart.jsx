import React, { useEffect, useMemo, useState } from "react";
import { fetchAllInvoices } from "../../services/api/invoiceService";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from "recharts";

const HEADER_COLOR = "#1C3557";

const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

const FILTERS = [
  { id: "all", label: "Geral", color: "#583D69" },
  { id: "1", label: "Eletricidade", color: "#1C3557" },
  { id: "2", label: "Água", color: "#0F766E" },
  { id: "3", label: "Gás", color: "#EA580C" },
];

const CATEGORY_LABELS = {
  consumption: "Consumo",
  fixed_fee: "Tarifa fixa",
  tax: "Imposto / taxa",
  other: "Outro",
};

const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

const number = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 3,
});

// Converte valores numéricos, incluindo formatos decimais portugueses.
function asNumber(value) {
  if (typeof value === "number") return Number.isFinite(value) ? value : 0;
  if (value == null || value === "") return 0;

  let text = String(value).trim().replace(/\s/g, "").replace(/[€]/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);

  return Number.isFinite(result) ? result : 0;
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
  if (name.includes("água") || name.includes("agua") || name.includes("water")) return 2;
  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

// Obtém a lista de itens disponíveis no consumo.
function getItems(consumption) {
  const itemLists = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return (
    itemLists.find((items) => Array.isArray(items) && items.length > 0) ??
    itemLists.find(Array.isArray) ??
    []
  );
}

// Obtém o custo de cada item.
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

// Obtém a quantidade e a unidade de cada item.
function itemAmount(item) {
  return asNumber(item?.amount ?? item?.quantity ?? item?.consumption ?? 0);
}

function itemUnit(item) {
  return String(
    item?.unit ??
      item?.measurement_unit ??
      item?.unit_of_measure ??
      ""
  ).trim();
}

// Obtém o nome do item, utilizando uma designação alternativa se necessário.
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

// Constrói a descrição com categoria, detalhes e quantidade.
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

// Normaliza os nomes para permitir agrupar itens equivalentes.
function normalizeItemName(name) {
  return String(name ?? "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");
}

// Transforma os consumos das faturas em itens e agrega itens equivalentes.
function normalizeInvoices(invoices) {
  const result = [];

  (invoices ?? []).forEach((invoice, invoiceIndex) => {
    const consumptions = invoice?.consumptions ?? invoice?.utilities ?? [];

    if (!Array.isArray(consumptions)) return;

    const invoiceKey = String(invoice?.id ?? `invoice-${invoiceIndex}`);

    consumptions.forEach((consumption, consumptionIndex) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      const utility = UTILITIES[typeId];
      const items = getItems(consumption);

      // Adiciona um item normalizado à lista de resultados.
      const pushItem = (item, itemIndex, cost, fallback = false) => {
        const amount = fallback
          ? asNumber(consumption?.amount ?? consumption?.quantity)
          : itemAmount(item);

        const unit = fallback
          ? itemUnit(consumption)
          : itemUnit(item);

        result.push({
          id: [
            invoice?.id ?? invoiceIndex,
            consumption?.id ?? consumptionIndex,
            item?.id ?? itemIndex,
          ].join("-"),
          invoiceKey,
          typeId,
          utility: utility.name,
          color: utility.color,
          name: fallback
            ? `${utility.name} — total`
            : itemName(item, itemIndex),
          description: fallback
            ? (
                amount !== 0
                  ? `${number.format(amount)}${unit ? ` ${unit}` : ""} · Total do consumo`
                  : "Total do consumo"
              )
            : itemDescription(item),
          category: fallback ? "" : item?.category ?? "",
          categoryLabel: fallback
            ? ""
            : CATEGORY_LABELS[item?.category] ?? item?.category ?? "",
          amount,
          unit,
          cost,
          sliceValue: Math.abs(cost),
          isCredit: cost < 0,
        });
      };

      // Utiliza os itens individuais ou o total do consumo como alternativa.
      if (items.length) {
        items.forEach((item, itemIndex) => {
          pushItem(item, itemIndex, itemCost(item));
        });
      } else {
        pushItem(
          consumption,
          0,
          asNumber(
            consumption?.cost ??
              consumption?.total_cost ??
              consumption?.totalCost ??
              0
          ),
          true
        );
      }
    });
  });

  // Agrupa itens com o mesmo tipo de serviço e nome normalizado.
  const grouped = new Map();

  result.forEach((item) => {
    const key = `${item.typeId}-${normalizeItemName(item.name)}`;

    if (!grouped.has(key)) {
      grouped.set(key, {
        ...item,
        invoiceKeys: item.invoiceKey ? [item.invoiceKey] : [],
        descriptions: item.description ? [item.description] : [],
        categories: item.categoryLabel ? [item.categoryLabel] : [],
      });

      return;
    }

    const existing = grouped.get(key);

    existing.cost += item.cost;
    existing.amount += item.amount;

    if (item.invoiceKey && !existing.invoiceKeys.includes(item.invoiceKey)) {
      existing.invoiceKeys.push(item.invoiceKey);
    }

    if (item.description && !existing.descriptions.includes(item.description)) {
      existing.descriptions.push(item.description);
    }

    if (item.categoryLabel && !existing.categories.includes(item.categoryLabel)) {
      existing.categories.push(item.categoryLabel);
    }
  });

  // Prepara os dados finais e ordena os itens por serviço e nome.
  return Array.from(grouped.values())
    .map((item) => {
      const quantityText =
        item.amount !== 0
          ? `${number.format(item.amount)}${item.unit ? ` ${item.unit}` : ""}`
          : "";

      const descriptionParts = [
        ...item.descriptions
          .flatMap((description) => description.split(" · "))
          .filter((part) => {
            const value = part.trim().toLowerCase();

            const isQuantity =
              /^[\d.,]+\s(kwh|mwh|m3|m³|l|litros?|kw|kva)$/i.test(value);

            const isTaxBase =
              /^[\d.,]+\s(eur|€)\s+de\s+base$/i.test(value);

            return !isQuantity && !isTaxBase;
          }),
        quantityText,
      ]
        .map((part) => String(part ?? "").trim())
        .filter(Boolean)
        .filter(
          (part, index, array) =>
            array.findIndex(
              (value) => value.toLowerCase() === part.toLowerCase()
            ) === index
        );

      const { invoiceKeys, ...groupedItem } = item;

      return {
        ...groupedItem,
        id: `${item.typeId}-${normalizeItemName(item.name)}`,
        sliceValue: Math.abs(item.cost),
        isCredit: item.cost < 0,
        description: descriptionParts.join(" · "),
        invoiceCount: invoiceKeys.length,
      };
    })
    .sort((a, b) =>
      a.typeId !== b.typeId
        ? a.typeId - b.typeId
        : a.name.localeCompare(b.name, "pt-PT")
    );
}

// Apresenta os detalhes do item selecionado no gráfico.
function ChartTooltip({ active, payload }) {
  if (!active || !payload?.length) return null;

  const item = payload[0]?.payload;

  if (!item) return null;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
      <p className="font-semibold text-slate-800">{item.name}</p>

      <p className="mt-1 text-sm text-slate-500">{item.utility}</p>

      {item.description && (
        <p className="mt-2 max-w-64 text-xs text-slate-500">
          {item.description}
        </p>
      )}

      <p
        className={`mt-2 font-semibold ${
          item.isCredit ? "text-emerald-700" : "text-slate-900"
        }`}
      >
        {money.format(item.cost)}
        {item.isCredit ? " · Crédito" : ""}
      </p>

      {item.invoiceCount > 0 && (
        <p className="mt-1 max-w-64 text-xs text-slate-400">
          Incluído em {item.invoiceCount}{" "}
          {item.invoiceCount === 1 ? "fatura" : "faturas"}
        </p>
      )}
    </div>
  );
}

export default function DetailedConsumptionChart({
  invoices: invoicesProp,
  propertyId,
  refreshKey = 0,
  filter: filterProp,
  onFilterChange,
}) {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [internalFilter, setInternalFilter] = useState("all");
  const [activeItemId, setActiveItemId] = useState(null);

  // Permite utilizar o filtro controlado pelo componente pai ou internamente.
  const controlled = filterProp !== undefined;
  const filter = controlled ? String(filterProp) : internalFilter;

  // Usa as faturas recebidas por props; mantém o Service como alternativa.
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
        const allInvoices = await fetchAllInvoices(propertyId);

        if (!cancelled) {
          setInvoices(allInvoices);
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
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    load();

    return () => {
      cancelled = true;
    };
  }, [invoicesProp, propertyId, refreshKey]);

  // Normaliza as faturas e prepara os itens que serão apresentados.
  const allItems = useMemo(
    () => normalizeInvoices(invoices),
    [invoices]
  );

  // Aplica o filtro de serviço selecionado.
  const visibleItems = useMemo(
    () =>
      filter === "all"
        ? allItems
        : allItems.filter((item) => String(item.typeId) === filter),
    [allItems, filter]
  );

  const total = visibleItems.reduce((sum, item) => sum + item.cost, 0);

  // Prepara apenas os itens com valor absoluto positivo para o gráfico.
  const chartItems = useMemo(
    () =>
      visibleItems
        .filter((item) => item.sliceValue > 0)
        .sort((a, b) =>
          a.typeId !== b.typeId
            ? a.typeId - b.typeId
            : a.name.localeCompare(b.name, "pt-PT")
        ),
    [visibleItems]
  );

  // Agrupa os itens por serviço para apresentar subtotais e listas.
  const groups = [1, 2, 3]
    .filter((id) => filter === "all" || filter === String(id))
    .map((id) => ({
      id,
      ...UTILITIES[id],
      items: visibleItems.filter((item) => item.typeId === id),
    }))
    .filter((group) => group.items.length > 0);

  const selectedItem = visibleItems.find(
    (item) => item.id === activeItemId
  );

  // Limpa a seleção quando o item deixa de estar visível.
  useEffect(() => {
    if (
      activeItemId &&
      !visibleItems.some((item) => item.id === activeItemId)
    ) {
      setActiveItemId(null);
    }
  }, [activeItemId, visibleItems]);

  // Atualiza o filtro e limpa a seleção anterior.
  function selectFilter(id) {
    if (!controlled) {
      setInternalFilter(id);
    }

    onFilterChange?.(id);
    setActiveItemId(null);
  }

  // Apresenta o estado de carregamento enquanto os dados são obtidos.
  if (loading) {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <p className="text-sm text-slate-500">
          A carregar consumos...
        </p>
      </section>
    );
  }

  // Renderiza os filtros, o gráfico circular e os detalhes dos itens.
  return (
    <section className="w-full min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      {/* Cabeçalho fixo em azul da Eletricidade. */}
      <div
        className="relative -mx-4 -mt-4 mb-4 flex flex-col gap-4 overflow-hidden rounded-t-2xl p-5 text-white shadow-sm sm:-mx-6 sm:-mt-6 sm:flex-row sm:items-center sm:justify-between sm:p-6"
        style={{ backgroundColor: HEADER_COLOR }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />

        <div className="relative z-10">
          <h2 className="text-lg font-semibold text-white">
            Detalhe dos consumos
          </h2>

          <p className="mt-1 text-sm text-white/80">
            Custos detalhados por serviço e por item de fatura.
          </p>
        </div>

        <div className="relative z-10 flex flex-wrap gap-2">
          {FILTERS.map((option) => {
            const active = filter === option.id;

            return (
              <button
                key={option.id}
                type="button"
                onClick={() => selectFilter(option.id)}
                className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                  active
                    ? "border-white bg-white shadow-sm"
                    : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                }`}
                style={
                  active
                    ? { color: option.color }
                    : { color: "#FFFFFF" }
                }
              >
                {option.label}
              </button>
            );
          })}
        </div>
      </div>

      {error && (
        <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
          {error}
        </p>
      )}

      {!visibleItems.length ? (
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
          {/* Gráfico circular e lista de serviços. */}
          <div className="mt-6 grid w-full min-w-0 grid-cols-1 items-start gap-6 sm:gap-8 lg:grid-cols-[minmax(280px,0.85fr)_minmax(0,1.15fr)]">
            <div className="min-w-0">
              <div className="relative h-[320px]">
                {chartItems.length > 0 ? (
                  <>
                    <ResponsiveContainer width="100%" height="100%">
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
                          {chartItems.map((item) => {
                            const active = activeItemId === item.id;

                            return (
                              <Cell
                                key={item.id}
                                fill={item.color}
                                stroke={active ? "#0f172a" : "#ffffff"}
                                strokeWidth={active ? 4 : 2}
                                opacity={
                                  activeItemId && !active ? 0.28 : 1
                                }
                              />
                            );
                          })}
                        </Pie>

                        <Tooltip
                          content={<ChartTooltip />}
                          wrapperStyle={{
                            zIndex: 9999,
                            pointerEvents: "none",
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>

                    {/* Mostra o total ou os dados do item selecionado. */}
                    <div
                      className="pointer-events-none absolute inset-0 flex items-center justify-center"
                      style={{ zIndex: 1 }}
                    >
                      <div className="flex w-[150px] max-w-[46%] flex-col items-center text-center">
                        {selectedItem ? (
                          <>
                            <span className="line-clamp-3 break-words text-xs leading-4 text-slate-500">
                              {selectedItem.name}
                            </span>

                            <strong className="mt-1 text-xl text-slate-900">
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
                              {visibleItems.length === 1 ? "item" : "itens"}
                            </span>
                          </>
                        )}
                      </div>
                    </div>
                  </>
                ) : (
                  <div className="flex h-full items-center justify-center text-center text-sm text-slate-500">
                    Não existem custos diferentes de zero para desenhar o gráfico.
                  </div>
                )}
              </div>

              {/* Subtotais de cada serviço. */}
              <div className="mt-3 space-y-2">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0
                  );

                  return (
                    <div
                      key={group.id}
                      className="flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <span className="flex min-w-0 items-center gap-2 text-sm text-slate-600">
                        <span
                          className="h-2.5 w-2.5 rounded-full"
                          style={{ backgroundColor: group.color }}
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
            </div>

            {/* Painel lateral scrollável com os itens agrupados. */}
            <div className="min-w-0">
              <div className="max-h-[420px] min-w-0 space-y-3 overflow-y-auto overscroll-contain pr-1 sm:pr-2 [scrollbar-gutter:stable]">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0
                  );

                  return (
                    <div key={group.id}>
                      <div className="sticky top-0 z-10 mb-1 flex items-center justify-between gap-2 border-b border-slate-200 bg-white/95 pb-1.5 pt-1 backdrop-blur-sm">
                        <div className="flex min-w-0 items-center gap-1.5">
                          <span
                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                            style={{ backgroundColor: group.color }}
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

                      {/* Apresenta cada item e permite destacar o respetivo setor. */}
                      <div className="space-y-0.5">
                        {group.items.map((item) => {
                          const active = activeItemId === item.id;
                          const dimmed =
                            activeItemId !== null && !active;

                          const invoiceText =
                            item.invoiceCount > 0
                              ? `Incluído em ${item.invoiceCount} ${
                                  item.invoiceCount === 1
                                    ? "fatura"
                                    : "faturas"
                                }`
                              : "";

                          const metadataParts = [
                            item.categoryLabel,
                            ...String(item.description ?? "").split(" · "),
                            invoiceText,
                          ]
                            .map((value) => String(value ?? "").trim())
                            .filter(Boolean)
                            .filter(
                              (value, index, array) =>
                                array.findIndex(
                                  (other) =>
                                    other.toLowerCase() === value.toLowerCase()
                                ) === index
                            );

                          const metadata = metadataParts.join(" · ");

                          return (
                            <div
                              key={item.id}
                              onMouseEnter={() => setActiveItemId(item.id)}
                              onMouseLeave={() => setActiveItemId(null)}
                              className={`flex items-start gap-2 rounded-md px-2 py-1 transition ${
                                active ? "bg-slate-50" : ""
                              }`}
                              style={{ opacity: dimmed ? 0.35 : 1 }}
                            >
                              <span
                                className="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                style={{ backgroundColor: item.color }}
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

          {/* Total líquido correspondente ao filtro selecionado. */}
          <div className="mt-5 flex flex-col items-start justify-between gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:gap-3">
            <span className="text-sm text-slate-500">
              Total líquido do filtro selecionado
            </span>

            <strong
              className={`text-lg ${
                total < 0 ? "text-emerald-700" : "text-slate-900"
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
