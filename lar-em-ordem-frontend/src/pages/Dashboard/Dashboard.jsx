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
          <div className="rounded-2xl bg-card p-6 shadow-sm text-muted">
            (cartões e atalhos)
          </div>
        </div>
      </div>
    </div>
  );
}