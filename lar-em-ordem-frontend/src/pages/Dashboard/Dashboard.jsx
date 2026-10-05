import { Droplet, Zap, Flame, FolderClosed } from 'lucide-react';
import { SummaryCard } from '../../components/ui/SummaryCard';
import { ShortcutCard } from '../../components/ui/ShortcutCard';
import { ConsumptionChart } from '../../components/ui/ConsumptionChart';

export default function Dashboard() {
  return (
    <div className="flex flex-col gap-6">
      <header>
        <h1 className="font-display text-3xl text-navy">Painel da Habitação</h1>
      </header>

      <div className="flex flex-col lg:flex-row gap-6">
        <div className="flex-1">
          <div className="rounded-2xl bg-card p-6 shadow-sm text-muted">
            <ConsumptionChart />
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