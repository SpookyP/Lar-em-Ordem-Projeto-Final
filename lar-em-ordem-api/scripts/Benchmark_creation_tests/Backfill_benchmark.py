from datetime import date
from sqlalchemy import text
from Benchmark_core import build_month, build_year, engine, month_start, next_month


with engine.connect() as conn:
    first = conn.execute(text("SELECT MIN(period_start) FROM consumptions")).scalar()

if first is None:
    raise SystemExit("Sem consumos para processar.")


current = month_start(first)
this_month = month_start(date.today())   


while current < this_month:
    n = build_month(current)
    print(f"{current:%Y-%m}: {n} mensais")
    current = next_month(current)

for year in range(first.year, date.today().year):  
    print(f"{year}: {build_year(year)} anuais")
