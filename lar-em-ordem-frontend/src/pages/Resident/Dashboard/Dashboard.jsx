import { useState } from "react";
import { Zap, FolderClosed } from "lucide-react";

import { ShortcutCard } from "../../../components/ui/ShortcutCard";
import ConsumptionChart from "../../../components/ui/ConsumptionChart";
import ConsumptionSummary from "../../../components/ui/ConsumptionSummary";
import { NotificationBell } from "../../../components/ui/NotificationBell";
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

  // Obtém as faturas através do hook centralizado.
  const {
    invoices,
    loading,
    error,
  } = useInvoices();

  return (
    <div className="flex flex-col gap-6">

      {/* Cabeçalho do painel e notificações. */}
      <header className="flex items-center justify-between gap-3 w-full">
        <h1 className="min-w-0 font-display text-2xl sm:text-3xl text-navy">
          Painel da Habitação
        </h1>

        <NotificationBell />
      </header>

      {/* Área principal: gráfico de consumos e resumo lateral. */}
      <div className="flex flex-col lg:flex-row gap-6">

        {/* Gráfico de distribuição dos gastos. */}
        <div className="min-w-0 flex-1">
          <div className="rounded-2xl bg-card p-4 sm:p-8 shadow-sm">

            {/* Título e seleção do período. */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
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

              {/* Em telemóveis, os períodos mantêm-se numa única linha. */}
              <div className="flex w-full max-w-full overflow-x-auto rounded-full bg-surface p-1 sm:w-auto">
                {periods.map((p) => (
                  <button
                    key={p.value}
                    type="button"
                    onClick={() => setPeriod(p.value)}
                    aria-pressed={period === p.value}
                    className={`flex-1 sm:flex-none whitespace-nowrap rounded-full px-3 py-1 text-sm transition ${
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
            <p className="text-xs text-muted mt-6">
              * Conversões: Água — 1 m³ = 1 000 L | Gás — 1 kWh ≈ 0,076–0,087 m³
            </p>
          </div>
        </div>

        {/* Resumo dos consumos e atalhos de navegação. */}
        <div className="w-full lg:w-80 flex flex-col gap-3">

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

          {/* Atalho para a gestão de consumos e faturas. */}
          <ShortcutCard
            icon={Zap}
            iconColor="bg-teal-light text-teal"
            title="Consumos"
            description="Registar fatura"
            to="/consumos"
          />
        </div>
      </div>
    </div>
  );
}
