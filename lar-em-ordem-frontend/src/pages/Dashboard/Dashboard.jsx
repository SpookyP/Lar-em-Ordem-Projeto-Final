import { Droplet, Zap, Flame, FolderClosed } from "lucide-react";
import { SummaryCard } from "../../components/ui/SummaryCard";
import { ShortcutCard } from "../../components/ui/ShortcutCard";
import { ConsumptionChart } from "../../components/ui/ConsumptionChart";
import { useState } from "react";

const periods = ["1 mês", "2 meses", "6 meses"];

export default function Dashboard() {
  const [period, setPeriod] = useState("1 mês");

  return (
    <div className="flex flex-col gap-6">
      <header>
        <h1 className="font-display text-3xl text-navy">Painel da Habitação</h1>
      </header>

      <div className="flex flex-col lg:flex-row gap-6">
        <div className="flex-1">
          <div className="rounded-2xl bg-card p-8 shadow-sm">
            {/* cabecalho do cartao */}
            <div className="flex items-start justify-between mb-6">
              <div>
                <h2 className="font-display text-xl text-navy">Gasto Mensal</h2>
                <p className="text-sm text-muted">Último mês · Setembro 2026</p>
              </div>

              <div className="flex rounded-full bg-surface p-1">
                {periods.map((p) => (
                  <button
                    hey={p}
                    onClick={() => setPeriod(p)}
                    className={`rounded-full px-3 py-1 text-sm transition ${
                      period === p
                        ? "bg-card text-navy shadow-sm"
                        : "text-muted hover:text-navy"
                    }`}
                  >
                    {p}
                  </button>
                ))}
              </div>
            </div>

            <ConsumptionChart />

            <p className="text-xs text-muted mt-6 ">
              * Conversão: Água - 1m³ = 1000 L | Gás - 1m³ ~ 11,5 a 13,2 kWh
            </p>
          </div>
        </div>

        <div className="w-full lg:w-80 flex flex-col gap-3">
          <SummaryCard
            icon={Droplet}
            iconColor="bg-teal-light text-teal"
            value="15 m³"
            label="Água · este mês"
          />
          <SummaryCard
            icon={Zap}
            iconColor="bg-amber-light text-amber"
            value="61 kWh"
            label="Eletricidade · este mês"
          />
          <SummaryCard
            icon={Flame}
            iconColor="bg-orange-light text-orange"
            value="8 m³"
            label="Gás · este mês"
          />
          <ShortcutCard
            icon={FolderClosed}
            iconColor="bg-navy/10 text-navy"
            title="Cofre Digital"
            description="1 documento a expirar"
            to="/cofre"
          />
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
