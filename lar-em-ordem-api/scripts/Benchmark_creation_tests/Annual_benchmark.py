import argparse
from datetime import date
from Benchmark_core import build_year

ap = argparse.ArgumentParser()
ap.add_argument("--year", type=int, help="por defeito: ano anterior")
args = ap.parse_args()


year = args.year or date.today().year - 1
n = build_year(year)
print(f"{year}: {n} benchmarks anuais gravados")
