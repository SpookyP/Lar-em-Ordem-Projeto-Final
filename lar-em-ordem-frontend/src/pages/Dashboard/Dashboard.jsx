import { Droplet, Zap, Flame } from 'lucide-react';
import { SummaryCard } from '../../components/ui/SummaryCard';

export default function Dashboard() {
  return (
    <div className="flex flex-col gap-6">
      <header>
        <h1 className="font-display text-3xl text-navy">Painel da Habitação</h1>
      </header>

      <div className="flex flex-col lg:flex-row gap-6">
        <div className="flex-1">
          <div className="rounded-2xl bg-card p-6 shadow-sm text-muted">
            (gráfico de consumos)
          </div>
        </div>

        <div className="w-full lg:w-80 flex flex-col gap-3">
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
          </div>
        </div>
      </div>
    </div>
  );
}