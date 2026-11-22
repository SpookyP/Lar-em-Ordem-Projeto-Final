"""Monthly benchmark.
By default it reprocesses the last 3 complete months (this catches invoices registered late).
Examples:
  python monthly_benchmark.py                     # last 3 months
  python monthly_benchmark.py --months-back 6     # last 6 months
  python monthly_benchmark.py --month 2026-09     # September 2026 only
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
    # Starts at last month (the current one isn't complete yet)
    target = month_start(month_start(date.today()) - timedelta(days=1))
    months_to_process = args.months_back

for _ in range(months_to_process):
    n = build_month(target)
    print(f"{target:%Y-%m}: {n} benchmarks mensais gravados")
    target = month_start(target - timedelta(days=1))
