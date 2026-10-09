"""Backfill: cria os benchmarks históricos (todos os meses e anos completos com dados).
Corre-se uma vez; depois os agendamentos mensal e anual mantêm tudo atualizado."""

from datetime import date
from sqlalchemy import text
from benchmark_core import build_month, build_year, engine, month_start, next_month

with engine.connect() as conn:
    first = conn.execute(text("SELECT MIN(period_start) FROM consumptions")).scalar()

if first is None:
    raise SystemExit("Sem consumos para processar.")

if hasattr(first, "date"):
    first = first.date()

current = month_start(first)
this_month = month_start(date.today())  # o mês em curso não está completo

# Todos os meses completos, do mais antigo até ao mês passado
monthly_total = 0
while current < this_month:
    monthly_total += build_month(current)
    current = next_month(current)

# Todos os anos completos (o ano em curso fica de fora)
annual_total = 0
for year in range(first.year, date.today().year):  # só anos completos
    annual_total += build_year(year)

print(f"Monthly: {monthly_total}")
print(f"Annual: {annual_total}")