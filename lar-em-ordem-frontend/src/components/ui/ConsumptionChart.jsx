import { PieChart, Pie, ResponsiveContainer, Sector } from "recharts";
import { useState } from "react";
import { ChevronRight } from "lucide-react";

//dados de exemplo
const data = [
  { name: "Eletricidade", value: 53.1, color: "#1C3557" },
  { name: "Água", value: 23.85, color: "#0F766E" },
  { name: "Gás", value: 15.55, color: "#EA580C" },
];

export function ConsumptionChart() {
  const [focused, setFocused] = useState(null);
  const total = data.reduce((sum, item) => sum + item.value, 0);

  const center =
    focused !== null
      ? { label: data[focused].name, value: data[focused].value }
      : { label: "Total", value: total };

  const biggest = data.reduce((max, item) =>
    item.value > max.value ? item : max,
  );

  return (
    <div className="flex flex-col lg:flex-row items-center gap-24">
      {/* grafico de anel */}
      <div className="relative shrink-0" style={{ width: 260, height: 260 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              cx="50%"
              cy="50%"
              innerRadius={82}
              outerRadius={120}
              startAngle={90}
              endAngle={-270}
              paddingAngle={2}
              stroke="none"
              onMouseEnter={(_, index) => setFocused(index)}
              onMouseLeave={() => setFocused(null)}
              shape={(props) => {
                const { index } = props;
                const opacity = focused === null || focused === index ? 1 : 0.3;
                return (
                  <Sector
                    {...props}
                    fill={data[index].color}
                    opacity={opacity}
                    style={{ transition: "opacity 0.18s" }}
                  />
                );
              }}
            />
          </PieChart>
        </ResponsiveContainer>

        <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <p className="text-xs uppercase tracking-wide text-muted">
            {center.label}
          </p>
          <p className="font-display text-3xl text-navy leading-none">
            €{center.value.toFixed(2)}
          </p>
        </div>
      </div>

      {/* legenda */}
      <div className="w-full max-w-sm">
        <p className="text-sm text-muted">Maior gasto do período</p>
        <p className="font-display text-2xl text-navy mb-5">
          {biggest.name} · €{biggest.value.toFixed(2)}
        </p>

        <ul className="flex flex-col gap-3">
          {data.map((item, index) => {
            const percent = Math.round((item.value / total) * 100);
            return (
              <li
                key={item.name}
                onMouseEnter={() => setFocused(index)}
                onMouseLeave={() => setFocused(null)}
                className="flex items-center gap-3 cursor-pointer"
              >
                <span
                  className="h-3 w-3 rounded-full shrink-0"
                  style={{ backgroundColor: item.color }}
                />
                <span className="flex-1 text-sm text-text">{item.name}</span>
                <span className="text-sm text-muted">{percent}%</span>
                <span className="text-sm font-medium text-navy w-16 text-right">
                  €{item.value.toFixed(2)}
                </span>
                <ChevronRight size={16} className="text-muted" />
              </li>
            );
          })}
        </ul>

        <p className="text-xs text-muted mt-5">
          Clique numa categoria para ver o detalhe da fatura.
        </p>
      </div>
    </div>
  );
}
