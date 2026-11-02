import { useEffect, useState } from "react";
import { Droplet, Zap, Flame } from "lucide-react";

import { SummaryCard } from "../ui/SummaryCard";
import axiosClient from "../../services/api/axiosClient";

// Configuração dos cartões e das unidades de consumo.
const CONSUMPTION_CONFIG = {
  1: {
    name: "Eletricidade",
    icon: Zap,
    iconColor: "bg-amber-light text-amber",
    unit: "kWh",
  },
  2: {
    name: "Água",
    icon: Droplet,
    iconColor: "bg-teal-light text-teal",
    unit: "m³",
  },
  3: {
    name: "Gás",
    icon: Flame,
    iconColor: "bg-orange-light text-orange",
    unit: "kWh",
  },
};

// Valores iniciais dos totais.
const EMPTY_TOTALS = {
  1: 0,
  2: 0,
  3: 0,
};

// Obtém o ID do imóvel ativo.
const getActivePropertyId = () => {
  const storedHouse = localStorage.getItem("activeHouse");

  if (!storedHouse) return null;

  try {
    const activeHouse = JSON.parse(storedHouse);
    return activeHouse?.id ?? activeHouse ?? null;
  } catch {
    return storedHouse;
  }
};

export default function ConsumptionSummary() {
  // Guarda os totais e o estado de carregamento.
  const [totals, setTotals] = useState(EMPTY_TOTALS);
  const [loading, setLoading] = useState(true);

  // Carrega os consumos do imóvel ativo.
  useEffect(() => {
    let cancelled = false;

    const fetchConsumptions = async () => {
      const propertyId = getActivePropertyId();

      if (!propertyId) {
        setTotals(EMPTY_TOTALS);
        setLoading(false);
        return;
      }

      try {
        setLoading(true);

        const response = await axiosClient.get(
          "/api/v1/consumptions",
          {
            params: {
              property_id: propertyId,
            },
          }
        );

        // Obtém a lista de consumos da resposta da API.
        const consumptions =
          response.data?.data ?? response.data;

        if (!Array.isArray(consumptions)) {
          throw new Error("Formato de resposta da API inválido.");
        }

        // Identifica o mês e ano atuais.
        const currentMonth = new Date().getMonth();
        const currentYear = new Date().getFullYear();

        const newTotals = {
          ...EMPTY_TOTALS,
        };

        // Soma apenas os consumos do mês atual.
        consumptions.forEach((consumption) => {
          const dateValue =
            consumption.date ||
            consumption.issue_date ||
            consumption.created_at;

          if (!dateValue) return;

          const date = new Date(dateValue);

          if (Number.isNaN(date.getTime())) return;

          if (
            date.getMonth() !== currentMonth ||
            date.getFullYear() !== currentYear
          ) {
            return;
          }

          const typeId = Number(
            consumption.consumption_type_id
          );

          if (newTotals[typeId] === undefined) return;

          const amount = Number(consumption.amount || 0);

          if (Number.isFinite(amount)) {
            newTotals[typeId] += amount;
          }
        });

        if (!cancelled) {
          setTotals(newTotals);
        }
      } catch (error) {
        console.error(
          "Erro ao carregar resumo dos consumos:",
          error
        );
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    fetchConsumptions();

    // Evita atualizações depois de desmontar o componente.
    return () => {
      cancelled = true;
    };
  }, []);

  // Apresenta os três cartões de consumo.
  const cards = [1, 2, 3];

  return (
    <>
      {cards.map((typeId) => {
        const config = CONSUMPTION_CONFIG[typeId];
        const Icon = config.icon;

        return (
          <SummaryCard
            key={typeId}
            icon={Icon}
            iconColor={config.iconColor}
            value={
              loading
                ? "..."
                : `${totals[typeId].toLocaleString("pt-PT", {
                    maximumFractionDigits: 2,
                  })} ${config.unit}`
            }
            label={`${config.name} · este mês`}
          />
        );
      })}
    </>
  );
}
