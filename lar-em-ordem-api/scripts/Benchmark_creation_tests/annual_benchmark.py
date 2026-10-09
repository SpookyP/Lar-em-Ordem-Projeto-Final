"""Benchmark anual. Por defeito processa o ano anterior.
Exemplos:
  python annual_benchmark.py
  python annual_benchmark.py --year 2026
"""

import argparse
from datetime import date

from benchmark_core import build_year

ap = argparse.ArgumentParser()
ap.add_argument("--year", type=int, help="por defeito: ano anterior")
args = ap.parse_args()

year = args.year or date.today().year - 1
n = build_year(year)
print(f"{year}: {n} benchmarks anuais gravados")
