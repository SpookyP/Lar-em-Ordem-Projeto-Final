import argparse
from datetime import date, timedelta
from Benchmark_core import build_month, month_start

ap = argparse.ArgumentParser()

ap.add_argument("--month", type=str)
ap.add_argument("--months-back", type=int, default=3)

args = ap.parse_args()


if args.month:
    y, m = map(int, args.month.split("-"))
    target = date(y, m, 1)
    months_to_process = 1

else:
    target = month_start(
        month_start(date.today()) - timedelta(days=1)
    )
    months_to_process = args.months_back


for i in range(months_to_process):
    n = build_month(target)

    print(
        f"{target:%Y-%m}: "
        f"{n} benchmarks mensais gravados"
    )

    target = month_start(
        target - timedelta(days=1)
    )
