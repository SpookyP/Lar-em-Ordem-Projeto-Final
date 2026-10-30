import { useEffect, useMemo, useState } from "react";
import axiosClient from "../../services/api/axiosClient";
import {
  PieChart,
  Pie,
  ResponsiveContainer,
  Sector,
} from "recharts";
import { ChevronRight } from "lucide-react";

// Tipos de consumo e respetivas cores no gráfico.
const UTILITIES = {
  1: { name: "Eletricidade", color: "#1C3557" },
  2: { name: "Água", color: "#0F766E" },
  3: { name: "Gás", color: "#EA580C" },
};

// Formatação dos valores monetários em euros.
const money = new Intl.NumberFormat("pt-PT", {
  style: "currency",
  currency: "EUR",
});

// Converte valores recebidos da API para números.
function asNumber(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value)
    .trim()
    .replace(/\s/g, "")
    .replace(/[€]/g, "");

  // Converte valores portugueses, por exemplo 1.234,56.
  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);
  return Number.isFinite(result) ? result : 0;
}

// Obtém uma lista de faturas de diferentes formatos de resposta da API.
function responseArray(response) {
  const body = response?.data ?? response;

  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;
  if (Array.isArray(body?.data?.data)) return body.data.data;

  return [];
}

// Normaliza o ID do imóvel, aceitando também objetos e JSON.
function normalizePropertyId(value) {
  if (!value) return "";

  if (typeof value === "object") {
    return value.id ?? "";
  }

  if (typeof value === "string") {
    try {
      const parsed = JSON.parse(value);

      if (parsed && typeof parsed === "object") {
        return parsed.id ?? "";
      }
    } catch {
      // Se não for JSON, considera-se que já é um ID.
    }
  }

  return value;
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

  if (name.includes("eletric") || name.includes("electric")) {
    return 1;
  }

  if (
    name.includes("água") ||
    name.includes("agua") ||
    name.includes("water")
  ) {
    return 2;
  }

  if (name.includes("gás") || name.includes("gas")) {
    return 3;
  }

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
function getItemCost(item) {
  return asNumber(
    item?.cost ??
      item?.total_cost ??
      item?.totalCost ??
      item?.amount_cost ??
      item?.price ??
      0
  );
}

// Calcula o custo de um consumo sem duplicar os valores dos itens.
function getConsumptionCost(consumption) {
  const items = getItems(consumption);

  // Se existem linhas detalhadas, soma essas linhas.
  if (items.length > 0) {
    return items.reduce(
      (sum, item) => sum + getItemCost(item),
      0
    );
  }

  // Caso contrário, utiliza o custo geral do consumo.
  return asNumber(
    consumption?.cost ??
      consumption?.total_cost ??
      consumption?.totalCost ??
      0
  );
}

// Agrupa os custos de todas as faturas por tipo de consumo.
function normalizeInvoices(invoices) {
  const totals = {
    1: 0,
    2: 0,
    3: 0,
  };

  invoices.forEach((invoice) => {
    const consumptions =
      invoice?.consumptions ??
      invoice?.utilities ??
      [];

    if (!Array.isArray(consumptions)) return;

    consumptions.forEach((consumption) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      totals[typeId] += getConsumptionCost(consumption);
    });
  });

  return Object.entries(UTILITIES)
    .map(([id, utility]) => ({
      id: Number(id),
      name: utility.name,
      color: utility.color,
      value: Number(totals[id].toFixed(2)),
    }))
    .filter((item) => item.value !== 0);
}

export default function ConsumptionChart({
  propertyId,
  refreshKey = 0,
  invoices: invoicesProp,
  onCategoryClick,
}) {
  // Guarda as faturas, o estado de carregamento e os erros.
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Guarda o ID da categoria selecionada ou destacada.
  const [focusedId, setFocusedId] = useState(null);

  // Normaliza o imóvel antes de enviar o pedido à API.
  const normalizedPropertyId = normalizePropertyId(propertyId);

  // Carrega as faturas ou utiliza os dados recebidos por propriedades.
  useEffect(() => {
    let cancelled = false;

    async function loadInvoices() {
      // Se a página fornecer as faturas, não é necessário fazer outro pedido.
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
            params: normalizedPropertyId
              ? { property_id: normalizedPropertyId }
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
              "Não foi possível carregar os consumos."
          );
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    loadInvoices();

    // Impede atualizações de estado depois de o componente ser desmontado.
    return () => {
      cancelled = true;
    };
  }, [normalizedPropertyId, refreshKey, invoicesProp]);

  // Prepara os valores agregados que serão apresentados no gráfico.
  const data = useMemo(
    () => normalizeInvoices(invoices),
    [invoices]
  );

  // Calcula o custo total das categorias apresentadas.
  const total = useMemo(
    () => data.reduce((sum, item) => sum + item.value, 0),
    [data]
  );

  // Procura a categoria atualmente selecionada.
  const focusedItem = data.find(
    (item) => item.id === focusedId
  ) ?? null;

  // Define o conteúdo apresentado no centro do gráfico.
  const center = focusedItem
    ? {
        label: focusedItem.name,
        value: focusedItem.value,
      }
    : {
        label: "Total",
        value: total,
      };

  // Identifica a categoria com o maior custo.
  const biggest = data.reduce(
    (max, item) =>
      !max || item.value > max.value ? item : max,
    null
  );

  // Limpa a seleção caso os dados deixem de incluir essa categoria.
  useEffect(() => {
    if (
      focusedId !== null &&
      !data.some((item) => item.id === focusedId)
    ) {
      setFocusedId(null);
    }
  }, [data, focusedId]);

  // Apresenta o estado de carregamento.
  if (loading) {
    return (
      <div className="flex min-h-40 items-center justify-center">
        <p className="text-sm text-slate-500">
          A carregar consumos...
        </p>
      </div>
    );
  }

  // Apresenta eventuais erros da API.
  if (error) {
    return (
      <div className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
        {error}
      </div>
    );
  }

  // Apresenta uma mensagem quando não existem consumos.
  if (data.length === 0 || total === 0) {
    return (
      <div className="py-10 text-center">
        <p className="font-medium text-slate-700">
          Ainda não existem consumos para apresentar.
        </p>

        <p className="mt-1 text-sm text-slate-500">
          Adiciona uma fatura para consultar a distribuição
          dos custos.
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center gap-8 lg:flex-row lg:gap-12">
      {/* Gráfico de anel com destaque das categorias. */}
      <div
        className="relative shrink-0"
        style={{ width: 260, height: 260 }}
      >
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="name"
              cx="50%"
              cy="50%"
              innerRadius={82}
              outerRadius={120}
              startAngle={90}
              endAngle={-270}
              paddingAngle={2}
              stroke="none"

              // Desativa a animação para evitar interrupções no hover.
              isAnimationActive={false}

              // Atualiza a categoria destacada ao passar o rato.
              onMouseEnter={(_, index) => {
                setFocusedId(data[index]?.id ?? null);
              }}
              onMouseLeave={() => {
                setFocusedId(null);
              }}

              // Desenha cada fatia com a cor e opacidade adequadas.
              shape={(props) => {
                const { index } = props;
                const item = data[index];
                const isFocused = focusedId === item?.id;

                return (
                  <Sector
                    {...props}
                    fill={item?.color}
                    opacity={
                      focusedId === null || isFocused ? 1 : 0.3
                    }
                    style={{
                      transition: "opacity 0.18s",
                    }}
                  />
                );
              }}
            />
          </PieChart>
        </ResponsiveContainer>

        {/* Texto central com o total ou o valor selecionado. */}
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-5 text-center">
          <p className="text-xs uppercase tracking-wide text-slate-500">
            {center.label}
          </p>

          <p className="mt-1 text-3xl font-bold leading-none text-slate-900">
            {money.format(center.value)}
          </p>
        </div>
      </div>

      {/* Legenda dinâmica com os custos por categoria. */}
      <div className="w-full min-w-0 flex-1">
        {biggest && (
          <>
            <p className="text-sm text-slate-500">
              Maior gasto do período
            </p>

            <p className="mb-5 mt-1 text-2xl font-bold text-slate-900">
              {biggest.name} · {money.format(biggest.value)}
            </p>
          </>
        )}

        {/* Lista das categorias e respetivas percentagens. */}
        <ul className="flex flex-col gap-3">
          {data.map((item) => {
            const percent =
              total !== 0
                ? Math.round((item.value / total) * 100)
                : 0;

            const isFocused = focusedId === item.id;

            return (
              <li
                key={item.id}

                // Sincroniza o destaque com o gráfico.
                onMouseEnter={() => setFocusedId(item.id)}
                onMouseLeave={() => setFocusedId(null)}

                // Permite navegar e selecionar categorias pelo teclado.
                onFocus={() => setFocusedId(item.id)}
                onBlur={() => setFocusedId(null)}

                className={`flex items-center gap-3 rounded-lg px-2 py-2 transition ${
                  isFocused ? "bg-slate-50" : ""
                } ${
                  onCategoryClick ? "cursor-pointer" : ""
                }`}

                onClick={() => onCategoryClick?.(item)}
                role={onCategoryClick ? "button" : undefined}
                tabIndex={onCategoryClick ? 0 : undefined}

                // Permite selecionar uma categoria com Enter ou Espaço.
                onKeyDown={(event) => {
                  if (
                    onCategoryClick &&
                    (event.key === "Enter" || event.key === " ")
                  ) {
                    event.preventDefault();
                    onCategoryClick(item);
                  }
                }}
              >
                <span
                  className="h-3 w-3 shrink-0 rounded-full"
                  style={{ backgroundColor: item.color }}
                />

                <span className="min-w-0 flex-1 text-sm text-slate-700">
                  {item.name}
                </span>

                <span className="text-sm text-slate-500">
                  {percent}%
                </span>

                <span className="w-24 text-right text-sm font-semibold text-slate-900">
                  {money.format(item.value)}
                </span>

                {onCategoryClick && (
                  <ChevronRight
                    size={16}
                    className="shrink-0 text-slate-400"
                  />
                )}
              </li>
            );
          })}
        </ul>

        {/* Explica que as categorias podem abrir informação adicional. */}
        {onCategoryClick && (
          <p className="mt-5 text-xs text-slate-500">
            Clica numa categoria para consultar o detalhe.
          </p>
        )}
      </div>
    </div>
  );
}
