import { useEffect, useMemo, useState } from "react";
import { fetchAllInvoices } from "../../services/api/invoiceService";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from "recharts";
import {
  asNumber,
  getItems,
  getTypeId,
  itemCost,
} from "../ui/invoiceTimelineUtils";

// Define a cor principal do cabeçalho.
const HEADER_COLOR = "#1C3557";

// Define os tipos de consumo e respetivas cores.
const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

// Define as opções de filtro disponíveis.
const FILTERS = [
  { id: "all", label: "Geral", color: "#583D69" },
  { id: "1", label: "Eletricidade", color: "#1C3557" },
  { id: "2", label: "Água", color: "#0F766E" },
  { id: "3", label: "Gás", color: "#EA580C" },
];

// Traduz as categorias dos itens.
const CATEGORY_LABELS = {
  consumption: "Consumo",
  fixed_fee: "Tarifa fixa",
  tax: "Imposto / taxa",
  discount: "Desconto",
  other: "Outro",
};

// Formata os valores monetários em euros.
const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

// Formata os valores numéricos.
const number = new Intl.NumberFormat("pt-PT", {
  maximumFractionDigits: 3,
});

// Obtém a quantidade de um item.
function itemAmount(item) {
  return asNumber(item?.amount ?? item?.quantity ?? item?.consumption ?? 0);
}

// Obtém a unidade de medida de um item.
function itemUnit(item) {
  return String(
    item?.unit ?? item?.measurement_unit ?? item?.unit_of_measure ?? "",
  ).trim();
}

// Obtém o nome de um item.
function itemName(item, index) {
  return String(
    item?.name ??
      item?.description ??
      item?.label ??
      item?.concept ??
      item?.category ??
      `Item ${index + 1}`,
  ).trim();
}

// Constrói a descrição de um item.
function itemDescription(item) {
  const description = String(
    item?.description ??
      item?.details ??
      item?.notes ??
      item?.tariff ??
      item?.rate_name ??
      "",
  ).trim();

  const rawCategory = String(item?.category ?? "").trim();
  const category = CATEGORY_LABELS[rawCategory] ?? rawCategory;
  const amount = itemAmount(item);
  const unit = itemUnit(item);

  const quantityText =
    amount !== 0 ? `${number.format(amount)}${unit ? ` ${unit}` : ""}` : "";

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

// Normaliza nomes para facilitar a comparação.
function normalizeItemName(name) {
  return String(name ?? "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");
}

// Organiza e agrupa os consumos das faturas.
function normalizeInvoices(invoices) {
  const result = [];

  // Percorre todas as faturas e respetivos consumos.
  (invoices ?? []).forEach((invoice, invoiceIndex) => {
    const consumptions = invoice?.consumptions ?? invoice?.utilities ?? [];

    if (!Array.isArray(consumptions)) return;

    const invoiceKey = String(invoice?.id ?? `invoice-${invoiceIndex}`);

    // Percorre os consumos de cada fatura.
    consumptions.forEach((consumption, consumptionIndex) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      const utility = UTILITIES[typeId];
      const items = getItems(consumption);

      // Adiciona um item normalizado à lista.
      const pushItem = (item, itemIndex, cost, fallback = false) => {
        const amount = fallback
          ? asNumber(consumption?.amount ?? consumption?.quantity)
          : itemAmount(item);

        const unit = fallback ? itemUnit(consumption) : itemUnit(item);

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
            ? amount !== 0
              ? `${number.format(amount)}${unit ? ` ${unit}` : ""} · Total do consumo`
              : "Total do consumo"
            : itemDescription(item),
          categoryLabel: fallback
            ? ""
            : (CATEGORY_LABELS[item?.category] ?? item?.category ?? ""),
          amount,
          unit,
          cost,
          sliceValue: Math.abs(cost),
          isCredit: cost < 0,
        });
      };

      // Adiciona os itens ou o total do consumo.
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
              0,
          ),
          true,
        );
      }
    });
  });

  // Agrupa itens com o mesmo tipo e nome.
  const grouped = new Map();

  result.forEach((item) => {
    const key = `${item.typeId}-${normalizeItemName(item.name)}`;

    if (!grouped.has(key)) {
      grouped.set(key, {
        ...item,
        invoiceKeys: item.invoiceKey ? [item.invoiceKey] : [],
        descriptions: item.description ? [item.description] : [],
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
  });

  // Prepara os dados finais para apresentação.
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

            const isTaxBase = /^[\d.,]+\s(eur|€)\s+de\s+base$/i.test(value);

            return !isQuantity && !isTaxBase;
          }),
        quantityText,
      ]
        .map((part) => String(part ?? "").trim())
        .filter(Boolean)
        .filter(
          (part, index, array) =>
            array.findIndex(
              (value) => value.toLowerCase() === part.toLowerCase(),
            ) === index,
        );

      // Remove os campos auxiliares que não são necessários na apresentação.
      const groupedItem = { ...item };
      delete groupedItem.invoiceKeys;
      delete groupedItem.invoiceKey;

      return {
        ...groupedItem,
        id: `${item.typeId}-${normalizeItemName(item.name)}`,
        sliceValue: Math.abs(item.cost),
        isCredit: item.cost < 0,
        description: descriptionParts.join(" · "),
        invoiceCount: item.invoiceKeys.length,
      };
    })
    .sort((a, b) =>
      a.typeId !== b.typeId
        ? a.typeId - b.typeId
        : a.name.localeCompare(b.name, "pt-PT"),
    );
}

// Apresenta os detalhes de cada item ao passar sobre o gráfico.
function ChartTooltip({ active, payload }) {
  if (!active || !payload?.length) return null;

  const item = payload[0]?.payload;
  if (!item) return null;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
      <p className="text-base font-semibold text-slate-800">{item.name}</p>

      <p className="mt-1 text-sm text-slate-500">{item.utility}</p>

      {item.description && (
        <p className="mt-2 max-w-64 text-sm text-slate-500">
          {item.description}
        </p>
      )}

      <p
        className={`mt-2 text-base font-semibold ${
          item.isCredit ? "text-emerald-700" : "text-slate-900"
        }`}
      >
        {money.format(item.cost)}
        {item.isCredit ? " · Crédito" : ""}
      </p>

      {item.invoiceCount > 0 && (
        <p className="mt-1 max-w-64 text-sm text-slate-400">
          Incluído em {item.invoiceCount}{" "}
          {item.invoiceCount === 1 ? "fatura" : "faturas"}
        </p>
      )}
    </div>
  );
}

// Componente principal do gráfico de consumos.
export default function DetailedConsumptionChart({
  invoices: invoicesProp,
  propertyId,
  refreshKey = 0,
  filter: filterProp,
  onFilterChange,
}) {
  // Guarda as faturas carregadas e possíveis erros.
  const [fetched, setFetched] = useState({
    key: null,
    invoices: [],
    error: "",
  });

  // Controla o filtro e o item selecionado.
  const [internalFilter, setInternalFilter] = useState("all");
  const [activeItemId, setActiveItemId] = useState(null);

  // Verifica se o filtro é controlado pelo componente pai.
  const controlled = filterProp !== undefined;
  const filter = controlled ? String(filterProp) : internalFilter;

  const hasInvoicesProp = Array.isArray(invoicesProp);
  const requestKey = `${propertyId ?? ""}-${refreshKey}`;

  // Carrega as faturas quando não são recebidas por propriedades.
  useEffect(() => {
    if (hasInvoicesProp) return undefined;

    let cancelled = false;

    fetchAllInvoices(propertyId)
      .then((allInvoices) => {
        if (!cancelled) {
          setFetched({
            key: requestKey,
            invoices: allInvoices,
            error: "",
          });
        }
      })
      .catch((err) => {
        if (!cancelled) {
          setFetched({
            key: requestKey,
            invoices: [],
            error:
              err?.response?.data?.message ??
              "Não foi possível carregar as faturas.",
          });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [hasInvoicesProp, propertyId, requestKey]);

  // Define os dados, o estado de carregamento e os erros.
  const invoices = hasInvoicesProp ? invoicesProp : fetched.invoices;
  const loading = !hasInvoicesProp && fetched.key !== requestKey;
  const error = hasInvoicesProp || loading ? "" : fetched.error;

  // Normaliza os consumos das faturas.
  const allItems = useMemo(() => normalizeInvoices(invoices), [invoices]);

  // Aplica o filtro selecionado aos consumos.
  const visibleItems = useMemo(
    () =>
      filter === "all"
        ? allItems
        : allItems.filter((item) => String(item.typeId) === filter),
    [allItems, filter],
  );

  // Calcula o total dos consumos visíveis.
  const total = visibleItems.reduce((sum, item) => sum + item.cost, 0);

  // Prepara os dados apresentados no gráfico circular.
  const chartItems = useMemo(
    () =>
      visibleItems
        .filter((item) => item.sliceValue > 0)
        .sort((a, b) =>
          a.typeId !== b.typeId
            ? a.typeId - b.typeId
            : a.name.localeCompare(b.name, "pt-PT"),
        ),
    [visibleItems],
  );

  // Agrupa os itens por tipo de serviço.
  const groups = [1, 2, 3]
    .filter((id) => filter === "all" || filter === String(id))
    .map((id) => ({
      id,
      ...UTILITIES[id],
      items: visibleItems.filter((item) => item.typeId === id),
    }))
    .filter((group) => group.items.length > 0);

  // Identifica o item atualmente selecionado.
  const selectedItem = visibleItems.find((item) => item.id === activeItemId);
  const activeId = selectedItem ? selectedItem.id : null;

  // Atualiza o filtro e limpa a seleção anterior.
  function selectFilter(id) {
    if (!controlled) {
      setInternalFilter(id);
    }

    onFilterChange?.(id);
    setActiveItemId(null);
  }

  // Apresenta o estado de carregamento.
  if (loading) {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <p className="text-base text-slate-500">A carregar consumos...</p>
      </section>
    );
  }

  // Renderiza o gráfico e a lista de consumos.
  return (
    <section className="w-full min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      {/* Cabeçalho com título e filtros. */}
      <div
        className="relative -mx-4 -mt-4 mb-4 flex flex-col gap-4 overflow-hidden rounded-t-2xl p-5 text-white shadow-sm sm:-mx-6 sm:-mt-6 sm:flex-row sm:items-center sm:justify-between sm:p-6"
        style={{ backgroundColor: HEADER_COLOR }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />

        <div className="relative z-10">
          <h2 className="text-xl font-semibold text-white">
            Detalhe dos consumos
          </h2>

          <p className="mt-1 text-base text-white/80">
            Custos detalhados por serviço e por item de fatura.
          </p>
        </div>

        {/* Botões para filtrar os tipos de consumo. */}
        <div className="relative z-10 flex flex-wrap gap-2">
          {FILTERS.map((option) => {
            const active = filter === option.id;

            return (
              <button
                key={option.id}
                type="button"
                onClick={() => selectFilter(option.id)}
                className={`rounded-full border px-4 py-2 text-base font-medium transition ${
                  active
                    ? "border-white bg-white shadow-sm"
                    : "border-white/40 bg-white/10 text-white hover:bg-white/20"
                }`}
                style={active ? { color: option.color } : { color: "#FFFFFF" }}
              >
                {option.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Apresenta mensagens de erro, quando existem. */}
      {error && (
        <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
          {error}
        </p>
      )}

      {/* Apresenta uma mensagem quando não existem consumos. */}
      {!visibleItems.length ? (
        <div className="py-12 text-center">
          <p className="text-base font-medium text-slate-700">
            Ainda não existem consumos para apresentar.
          </p>

          <p className="mt-1 text-sm text-slate-500">
            Guarda uma fatura para consultar os seus detalhes.
          </p>
        </div>
      ) : (
        <>
          {/* Organiza o gráfico e a lista em duas colunas. */}
          <div className="mt-6 grid w-full min-w-0 grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(280px,0.9fr)_minmax(0,1.1fr)]">
            {/* Gráfico circular e totais por serviço. */}
            <div className="min-w-0">
              <div className="relative h-[320px] sm:h-[350px] xl:h-[380px]">
                {chartItems.length > 0 ? (
                  <>
                    {/* Desenha o gráfico com os custos dos consumos. */}
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
                            const active = activeId === item.id;

                            return (
                              <Cell
                                key={item.id}
                                fill={item.color}
                                stroke={active ? "#0f172a" : "#ffffff"}
                                strokeWidth={active ? 4 : 2}
                                opacity={activeId && !active ? 0.28 : 1}
                              />
                            );
                          })}
                        </Pie>

                        {/* Mostra os detalhes ao passar sobre uma fatia. */}
                        <Tooltip
                          content={<ChartTooltip />}
                          wrapperStyle={{
                            zIndex: 9999,
                            pointerEvents: "none",
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>

                    {/* Apresenta o total ou o item selecionado no centro. */}
                    <div
                      className="pointer-events-none absolute inset-0 flex items-center justify-center"
                      style={{ zIndex: 1 }}
                    >
                      <div className="flex w-[180px] max-w-[48%] flex-col items-center text-center">
                        {selectedItem ? (
                          <>
                            <span className="line-clamp-3 break-words text-base leading-5 text-slate-500">
                              {selectedItem.name}
                            </span>

                            <strong className="mt-1 text-2xl text-slate-900">
                              {money.format(selectedItem.cost)}
                            </strong>

                            <span className="mt-1 text-sm text-slate-500">
                              {selectedItem.utility}
                            </span>
                          </>
                        ) : (
                          <>
                            <span className="text-base text-slate-500">
                              Total líquido
                            </span>

                            <strong className="mt-1 text-2xl text-slate-900">
                              {money.format(total)}
                            </strong>

                            <span className="mt-1 text-sm text-slate-500">
                              {visibleItems.length}{" "}
                              {visibleItems.length === 1 ? "item" : "itens"}
                            </span>
                          </>
                        )}
                      </div>
                    </div>
                  </>
                ) : (
                  <div className="flex h-full items-center justify-center text-center text-base text-slate-500">
                    Não existem custos diferentes de zero para desenhar o
                    gráfico.
                  </div>
                )}
              </div>

              {/* Apresenta os totais separados por serviço. */}
              <div className="mt-3 space-y-2">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0,
                  );

                  return (
                    <div
                      key={group.id}
                      className="flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <span className="flex min-w-0 items-center gap-2 text-base text-slate-600">
                        <span
                          className="h-2.5 w-2.5 rounded-full"
                          style={{ backgroundColor: group.color }}
                        />
                        {group.name}
                      </span>

                      <strong
                        className={`text-base ${
                          groupTotal < 0 ? "text-emerald-700" : "text-slate-800"
                        }`}
                      >
                        {money.format(groupTotal)}
                      </strong>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Lista detalhada dos itens agrupados por serviço. */}
            <div className="min-w-0">
              <div className="max-h-[400px] min-w-0 space-y-3 overflow-y-auto overscroll-contain pr-1 sm:max-h-[440px] sm:pr-2 [scrollbar-gutter:stable]">
                {groups.map((group) => {
                  const groupTotal = group.items.reduce(
                    (sum, item) => sum + item.cost,
                    0,
                  );

                  return (
                    <div key={group.id}>
                      {/* Cabeçalho do grupo com o respetivo total. */}
                      <div className="sticky top-0 z-10 mb-1 flex items-center justify-between gap-2 border-b border-slate-200 bg-white/95 pb-1.5 pt-1 backdrop-blur-sm">
                        <div className="flex min-w-0 items-center gap-1.5">
                          <span
                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                            style={{ backgroundColor: group.color }}
                          />

                          <h3 className="truncate text-base font-bold text-slate-900">
                            {group.name}
                          </h3>
                        </div>

                        <strong
                          className={`shrink-0 text-base font-bold ${
                            groupTotal < 0
                              ? "text-emerald-700"
                              : "text-slate-900"
                          }`}
                        >
                          {money.format(groupTotal)}
                        </strong>
                      </div>

                      {/* Apresenta os itens e respetivos valores. */}
                      <div className="space-y-0.5">
                        {group.items.map((item) => {
                          const active = activeId === item.id;
                          const dimmed = activeId !== null && !active;

                          const invoiceText =
                            item.invoiceCount > 0
                              ? `Incluído em ${item.invoiceCount} ${
                                  item.invoiceCount === 1 ? "fatura" : "faturas"
                                }`
                              : "";

                          // Junta as informações complementares do item.
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
                                    other.toLowerCase() === value.toLowerCase(),
                                ) === index,
                            );

                          const metadata = metadataParts.join(" · ");

                          return (
                            <div
                              key={item.id}
                              onMouseEnter={() => setActiveItemId(item.id)}
                              onMouseLeave={() => setActiveItemId(null)}
                              className={`flex items-start gap-2 rounded-md px-2 py-1.5 transition ${
                                active ? "bg-slate-50" : ""
                              }`}
                              style={{ opacity: dimmed ? 0.35 : 1 }}
                            >
                              <span
                                className="mt-2 h-2 w-2 shrink-0 rounded-full"
                                style={{ backgroundColor: item.color }}
                              />

                              <div className="min-w-0 flex-1">
                                <p
                                  className="truncate text-base font-semibold leading-5 text-slate-800"
                                  title={item.name}
                                >
                                  {item.name}
                                </p>

                                {metadata && (
                                  <p
                                    className="mt-1 line-clamp-2 text-sm leading-5 text-slate-500"
                                    title={metadata}
                                  >
                                    {metadata}
                                  </p>
                                )}

                                {/* Identifica os valores que representam créditos. */}
                                {item.isCredit && (
                                  <span className="mt-1 inline-block rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold leading-4 text-emerald-700">
                                    Crédito / autoconsumo
                                  </span>
                                )}
                              </div>

                              {/* Apresenta o custo do item. */}
                              <strong
                                className={`shrink-0 whitespace-nowrap pt-0.5 text-base font-semibold ${
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

          {/* Apresenta o total final do filtro selecionado. */}
          <div className="mt-5 flex flex-col items-start justify-between gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:gap-3">
            <span className="text-base text-slate-500">
              Total líquido do filtro selecionado
            </span>

            <strong
              className={`text-xl ${
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
