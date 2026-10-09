"""Benchmark mensal.
Por defeito reprocessa os últimos 3 meses completos (apanha faturas registadas tarde).
Exemplos:
  python monthly_benchmark.py                     # últimos 3 meses
  python monthly_benchmark.py --months-back 6     # últumos 6 meses
  python monthly_benchmark.py --month 2026-09     # só setembro de 2026
"""

import argparse
from datetime import date, timedelta

from benchmark_core import build_month, month_start

ap = argparse.ArgumentParser()
ap.add_argument("--month", type=str, help="YYYY-MM (processa só esse mês)")
ap.add_argument("--months-back", type=int, default=3,
                help="meses anteriores a reprocessar (por defeito: 3)")
args = ap.parse_args()

if args.month:
    y, m = map(int, args.month.split("-"))
    target = date(y, m, 1)
    months_to_process = 1
else:
    # Começa no mês anterior (o mês em curso ainda está incompleto)
    target = month_start(month_start(date.today()) - timedelta(days=1))
    months_to_process = args.months_back

for _ in range(months_to_process):
    n = build_month(target)
    print(f"{target:%Y-%m}: {n} benchmarks mensais gravados")
    target = month_start(target - timedelta(days=1))
