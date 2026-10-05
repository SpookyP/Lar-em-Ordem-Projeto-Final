import { PieChart, Pie, ResponsiveContainer, Sector } from 'recharts';
import { useState } from 'react';

//dados de exemplo
const data = [
  { name: 'Eletricidade', value: 53.10, color: '#1C3557' },
  { name: 'Água', value: 23.85, color: '#0F766E' },
  { name: 'Gás', value: 15.55, color: '#EA580C' },
];

export function ConsumptionChart(){
    const [focused, setFocused] = useState(null);
    const total = data.reduce((sum, item) => sum + item.value, 0);

    const center = focused !== null ? {label: data[focused].name, value: data[focused].value} : { label: 'Total', value: total };
    return (
        <div className="relative" style={{ width: 240, height: 240 }}>
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                <Pie
                    data={data}
                    dataKey="value"
                    cx="50%"
                    cy="50%"
                    innerRadius={75}
                    outerRadius={110}
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
                            style={{ transition: 'opacity 0.18s' }}
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
    );
}