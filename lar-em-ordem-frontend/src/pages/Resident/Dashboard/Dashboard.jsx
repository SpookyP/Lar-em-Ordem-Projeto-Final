import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { Zap, FolderClosed, Plus, ChevronRight } from "lucide-react";

import { ShortcutCard } from "../../../components/ui/ShortcutCard";
import ConsumptionChart from "../../../components/ui/ConsumptionChart";
import ConsumptionSummary from "../../../components/ui/ConsumptionSummary";
import { NotificationBell } from "../../../components/ui/NotificationBell";
import { OfferRecommendations } from "../../../components/ui/OfferRecommendations";
import useInvoices from "../../../hooks/useInvoices";

// Períodos disponíveis para consultar os consumos.
const periods = [
  { label: "1 mês", value: 1 },
  { label: "3 meses", value: 3 },
  { label: "6 meses", value: 6 },
  { label: "12 meses", value: 12 },
];

export default function Dashboard() {
  const [period, setPeriod] = useState(1);
  const navigate = useNavigate();

  // Obter as faturas através do hook centralizado.
  const {
    invoices,
    loading,
    error,
  } = useInvoices();

  // Navegar para a página de consumos e abrir a zona de adicionar fatura.
  const handleAddInvoice = () => {
    navigate("/consumos", {
      state: { scrollToInvoiceForm: true },
    });
  };

  return (
    <div className="flex flex-col gap-6">

      {/* Cabeçalho do painel e notificações. */}
      <header className="flex w-full items-center justify-between gap-3">
        <h1 className="min-w-0 font-display text-2xl text-navy sm:text-3xl">
          Painel da Habitação
        </h1>

        <NotificationBell />
      </header>

      {/* Área principal: gráfico de consumos e resumo lateral. */}
      <div className="flex flex-col gap-6 lg:flex-row">

        {/* Gráfico de distribuição dos gastos. */}
        <div className="min-w-0 flex-1">
          <div className="rounded-2xl bg-card p-4 shadow-sm sm:p-8">

            {/* Título e seleção do período. */}
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <h2 className="font-display text-xl text-navy">
                  Gasto Mensal
                </h2>

                <p className="text-sm text-muted">
                  {period === 1
                    ? "Último mês"
                    : `Últimos ${period} meses`}
                </p>
              </div>

              {/* Seleção dos períodos. */}
              <div className="flex w-full max-w-full overflow-x-auto rounded-full bg-surface p-1 sm:w-auto">
                {periods.map((p) => (
                  <button
                    key={p.value}
                    type="button"
                    onClick={() => setPeriod(p.value)}
                    aria-pressed={period === p.value}
                    className={`flex-1 whitespace-nowrap rounded-full px-3 py-1 text-sm transition sm:flex-none ${
                      period === p.value
                        ? "bg-card text-navy shadow-sm"
                        : "text-muted hover:text-navy"
                    }`}
                  >
                    {p.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Gráfico circular com os custos por serviço. */}
            <ConsumptionChart
              invoices={invoices}
              loading={loading}
              error={error}
              period={period}
            />

            {/* Notas sobre as conversões de unidades. */}
            <p className="mt-6 text-xs text-muted">
              * Conversões: Água — 1 m³ = 1 000 L | Gás — 1 kWh ≈ 0,076–0,087 m³
            </p>
          </div>
        </div>

        {/* Resumo dos consumos e atalhos de navegação. */}
        <div className="flex w-full flex-col gap-3 lg:w-80">

          {/* Resumo dos valores físicos por tipo de consumo. */}
          <ConsumptionSummary
            invoices={invoices}
            loading={loading}
            error={error}
            period={period}
          />

          {/* Atalho para o cofre digital. */}
          <ShortcutCard
            icon={FolderClosed}
            iconColor="bg-navy/10 text-navy"
            title="Cofre Digital"
            description="1 documento a expirar"
            to="/cofre"
          />

          {/* Atalho para adicionar uma fatura. */}
          <button
            type="button"
            onClick={handleAddInvoice}
            className="group flex w-full items-center gap-4 rounded-2xl bg-card p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#1C3557] text-white">
              <Zap size={22} />
            </div>

            <div className="min-w-0 flex-1">
              <h3 className="font-semibold text-navy">
                Consumos
              </h3>

              <p className="mt-1 text-sm text-muted">
                Registar fatura
              </p>
            </div>

            <ChevronRight
              size={20}
              className="shrink-0 text-muted transition group-hover:translate-x-1 group-hover:text-[#1C3557]"
            />
          </button>
        </div>
      </div>

      {/* Recomendações de ofertas disponíveis. */}
      <div>
        <OfferRecommendations />
      </div>
    </div>
  );
}