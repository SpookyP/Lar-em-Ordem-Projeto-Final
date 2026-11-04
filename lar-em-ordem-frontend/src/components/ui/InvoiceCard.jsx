import { useCallback, useEffect, useState } from "react";
import axiosClient from "../../services/api/axiosClient";
import {
  Wallet,
  Zap,
  Droplets,
  Flame,
} from "lucide-react";

// Liga o ID do tipo de consumo da API à chave usada em 'totals'.
const SERVICE_TYPES = {
  1: "electricity",
  2: "water",
  3: "gas",
};

// Devolve o ID da casa ativa guardada no localStorage (ou "" se não existir).
function getPropertyId() {
  try {
    return JSON.parse(localStorage.getItem("activeHouse"))?.id || "";
  } catch {
    return "";
  }
}

// Extrai o array de faturas da resposta, aceitando vários formatos possíveis da API.
function getInvoices(response) {
  const body = response?.data ?? response;

  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.invoices)) return body.invoices;
  if (Array.isArray(body?.results)) return body.results;
  if (Array.isArray(body?.data?.data)) return body.data.data;

  return [];
}

// Converte qualquer valor para número, incluindo formato PT ("1.234,56 €"); inválido -> 0.
function toNumber(value) {
  if (value === null || value === undefined || value === "") return 0;
  if (typeof value === "number") return Number.isFinite(value) ? value : 0;

  let normalized = String(value).replace(/[€\s]/g, "");

  if (normalized.includes(",")) {
    normalized = normalized.replace(/\./g, "").replace(",", ".");
  }

  const number = Number(normalized);
  return Number.isFinite(number) ? number : 0;
}

// Descobre o tipo (1/2/3) de um consumo: primeiro pelo ID, depois pelo nome como alternativa.
function getTypeId(consumption) {
  const id =
    consumption?.consumption_type_id ??
    consumption?.consumption_type?.id;

  if (id && SERVICE_TYPES[id]) return Number(id);

  const name = String(
    consumption?.consumption_type?.name ??
    consumption?.type ??
    ""
  ).toLowerCase();

  if (name.includes("eletric") || name.includes("electric")) return 1;
  if (name.includes("água") || name.includes("agua")) return 2;
  if (name.includes("gás") || name.includes("gas")) return 3;

  return null;
}

// Custo de um consumo: se tiver itens de detalhe, soma o custo dos itens;
// caso contrário usa o custo total do próprio consumo.
// (Aceita vários nomes de campo para o mesmo valor.)
function getConsumptionCost(consumption) {
  const items =
    consumption?.items ??
    consumption?.consumption_items ??
    consumption?.consumptionItems ??
    consumption?.details ??
    consumption?.lines ??
    [];

  if (Array.isArray(items) && items.length > 0) {
    return items.reduce((total, item) => {
      return total + toNumber(
        item?.cost ??
        item?.total_cost ??
        item?.totalCost ??
        item?.amount_cost ??
        item?.price
      );
    }, 0);
  }

  return toNumber(
    consumption?.cost ??
    consumption?.total_cost ??
    consumption?.totalCost
  );
}

// Formata como moeda em euros (pt-PT).
function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "EUR",
  }).format(value);
}

export default function InvoiceCard() {
  // Totais mostrados nos cartões.
  const [totals, setTotals] = useState({
    total: 0,
    electricity: 0,
    water: 0,
    gas: 0,
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Vai buscar as faturas da casa ativa e recalcula todos os totais.
  
const fetchTotals = useCallback(async () => {
    const propertyId = getPropertyId();

    if (!propertyId) {
      setTotals({
        total: 0,
        electricity: 0,
        water: 0,
        gas: 0,
      });
      setLoading(false);
      return;
    }

    setLoading(true);
    setError("");

    try {
      // Buscar a primeira página
      const firstResponse = await axiosClient.get("/api/v1/invoices", {
        params: {
          property_id: propertyId,
          page: 1,
        },
      });

      const firstBody = firstResponse.data;
      let allInvoices = firstBody.data ?? [];

      // Descobrir quantas páginas existem
      const lastPage = Number(firstBody.meta?.last_page ?? 1);

      // Buscar todas as páginas restantes
      for (let page = 2; page <= lastPage; page++) {
        const response = await axiosClient.get("/api/v1/invoices", {
          params: {
            property_id: propertyId,
            page,
          },
        });

        allInvoices = allInvoices.concat(response.data.data ?? []);
      }

      console.log("Faturas carregadas para os cartões:", allInvoices.length);

      const result = {
        total: 0,
        electricity: 0,
        water: 0,
        gas: 0,
      };

      allInvoices.forEach((invoice) => {
        // Total geral da fatura
        result.total += toNumber(invoice?.total_amount);

        const consumptions = Array.isArray(invoice?.consumptions)
          ? invoice.consumptions
          : [];

        consumptions.forEach((consumption) => {
          const typeId = getTypeId(consumption);
          const service = SERVICE_TYPES[typeId];

          if (service) {
            result[service] += getConsumptionCost(consumption);
          }
        });
      });

      console.log("Totais calculados:", result);

      setTotals(result);

    } catch (err) {
      console.error("Erro ao calcular totais:", err);
      setError("Não foi possível carregar os totais.");
    } finally {
      setLoading(false);
    }
  }, []);


  // Carrega ao abrir e volta a carregar quando a casa ativa muda ou as faturas
  // são alteradas (eventos "activeHouseChanged" e "invoicesUpdated", ou localStorage).
  useEffect(() => {
    fetchTotals();

    window.addEventListener("activeHouseChanged", fetchTotals);
    window.addEventListener("storage", fetchTotals);
    window.addEventListener("invoicesUpdated", fetchTotals);

    return () => {
      window.removeEventListener("activeHouseChanged", fetchTotals);
      window.removeEventListener("storage", fetchTotals);
      window.removeEventListener("invoicesUpdated", fetchTotals);
    };
  }, [fetchTotals]);

  // Configuração dos 4 cartões (título, valor, ícone e cores).
  const cards = [
    {
      title: "Total de Despesas",
      value: totals.total,
      icon: Wallet,
      color: "bg-slate-100 text-slate-700",
    },
    {
      title: "Despesas de Eletricidade",
      value: totals.electricity,
      icon: Zap,
      color: "bg-blue-100 text-blue-700",
    },
    {
      title: "Despesas de Água",
      value: totals.water,
      icon: Droplets,
      color: "bg-teal-100 text-teal-700",
    },
    {
      title: "Despesas de Gás",
      value: totals.gas,
      icon: Flame,
      color: "bg-orange-100 text-orange-700",
    },
  ];

  return (
    <div>
      {error && (
        <p className="mb-4 text-sm text-red-600">{error}</p>
      )}

      {/* 1 coluna no telemóvel, 2 em ecrãs médios e 4 em ecrãs grandes */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => {
          const Icon = card.icon;

          return (
            <div
              key={card.title}
              className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
              <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-slate-500">
                  {card.title}
                </p>

                <div className={`rounded-xl p-2.5 ${card.color}`}>
                  <Icon size={20} />
                </div>
              </div>

              <p className="mt-4 text-2xl font-bold text-slate-900">
                {loading ? "..." : formatCurrency(card.value)}
              </p>
            </div>
          );
        })}
      </div>
    </div>
  );
}