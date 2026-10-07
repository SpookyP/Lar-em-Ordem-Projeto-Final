import os
from pathlib import Path
from datetime import date, timedelta
from collections import defaultdict
from dotenv import load_dotenv
from sqlalchemy import create_engine, text


# ============================================================
# CONFIGURAÇÃO
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[2]
load_dotenv(BASE_DIR / ".env")

MIN_SAMPLE = int(os.getenv("BENCHMARK_MIN_SAMPLE", "5"))
MIN_COVERAGE = 0.90
REGION_COLUMN = os.getenv("BENCHMARK_REGION_COLUMN", "district")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_DATABASE = os.getenv("DB_DATABASE")
DB_USERNAME = os.getenv("DB_USERNAME")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

if not DB_DATABASE:
    raise RuntimeError("DB_DATABASE não encontrado no ficheiro .env")

if not DB_USERNAME:
    raise RuntimeError("DB_USERNAME não encontrado no ficheiro .env")

engine = create_engine(
    f"mysql+pymysql://{DB_USERNAME}:{DB_PASSWORD}"
    f"@{DB_HOST}:{DB_PORT}/{DB_DATABASE}"
)


# ============================================================
# QUERIES
# ============================================================

FETCH = text(f"""
    SELECT
        c.property_id,
        c.consumption_type_id,
        c.period_start,
        c.period_end,
        c.amount,
        p.property_type_id,
        p.property_typology_id AS typology_id,
        a.{REGION_COLUMN} AS region
    FROM consumptions c
    INNER JOIN properties p ON p.id = c.property_id
    INNER JOIN addresses a ON a.id = p.address_id
    WHERE c.period_end >= :window_start
      AND c.period_start < :window_end
      AND p.deleted_at IS NULL
      AND a.{REGION_COLUMN} IS NOT NULL
""")

DELETE = text("""
    DELETE FROM consumption_benchmarks
    WHERE reference_period = :reference_period
      AND period_start = :period_start
""")

INSERT = text("""
    INSERT INTO consumption_benchmarks (
        consumption_type_id,
        property_type_id,
        typology_id,
        region,
        reference_period,
        period_start,
        average_value,
        sample_size
    )
    VALUES (
        :consumption_type_id,
        :property_type_id,
        :typology_id,
        :region,
        :reference_period,
        :period_start,
        :average_value,
        :sample_size
    )
""")


# ============================================================
# DATAS
# ============================================================

def month_start(d: date) -> date:
    return d.replace(day=1)


def next_month(d: date) -> date:
    return (d.replace(day=28) + timedelta(days=4)).replace(day=1)


# ============================================================
# CÁLCULO DOS BENCHMARKS
# ============================================================

def build_benchmarks(
    window_start: date,
    window_end: date,
    reference_period: str
) -> int:

    expected_days = (window_end - window_start).days

    # (property_id, consumption_type_id) -> [valor, dias_cobertos]
    property_consumption = defaultdict(lambda: [0.0, 0])

    # property_id -> características da propriedade
    property_data = {}

    with engine.connect() as conn:
        rows = conn.execute(
            FETCH,
            {
                "window_start": window_start,
                "window_end": window_end,
            }
        ).mappings().all()

    # Calcula o consumo correspondente ao período analisado
    for row in rows:
        start = row["period_start"]
        end = row["period_end"]

        if hasattr(start, "date"):
            start = start.date()
        if hasattr(end, "date"):
            end = end.date()

        if end < start:
            continue

        total_days = (end - start).days + 1

        overlap_start = max(start, window_start)
        overlap_end = min(
            end,
            window_end - timedelta(days=1)
        )

        if overlap_end < overlap_start:
            continue

        overlap_days = (overlap_end - overlap_start).days + 1

        key = (
            row["property_id"],
            row["consumption_type_id"]
        )

        value = float(row["amount"]) * overlap_days / total_days

        property_consumption[key][0] += value
        property_consumption[key][1] += overlap_days

        property_data[row["property_id"]] = {
            "property_type_id": row["property_type_id"],
            "typology_id": row["typology_id"],
            "region": row["region"],
        }

    # Agrupa propriedades equivalentes
    groups = defaultdict(list)

    for (property_id, consumption_type_id), (
        total_value,
        covered_days
    ) in property_consumption.items():

        if covered_days < expected_days * MIN_COVERAGE:
            continue

        # Estima o valor para o período completo
        value = total_value * expected_days / covered_days

        info = property_data[property_id]

        group_key = (
            consumption_type_id,
            info["property_type_id"],
            info["typology_id"],
            info["region"],
        )

        groups[group_key].append(value)

    # Apaga o benchmark anterior e grava o novo
    saved = 0

    with engine.begin() as conn:

        conn.execute(
            DELETE,
            {
                "reference_period": reference_period,
                "period_start": window_start,
            }
        )

        for (
            consumption_type_id,
            property_type_id,
            typology_id,
            region
        ), values in groups.items():

            if len(values) < MIN_SAMPLE:
                continue

            average_value = sum(values) / len(values)

            conn.execute(
                INSERT,
                {
                    "consumption_type_id": consumption_type_id,
                    "property_type_id": property_type_id,
                    "typology_id": typology_id,
                    "region": region,
                    "reference_period": reference_period,
                    "period_start": window_start,
                    "average_value": round(average_value, 3),
                    "sample_size": len(values),
                }
            )

            saved += 1

    return saved


# ============================================================
# BENCHMARK MENSAL
# ============================================================

def build_month(first_day: date) -> int:
    return build_benchmarks(
        first_day,
        next_month(first_day),
        "monthly"
    )


# ============================================================
# BENCHMARK ANUAL
# ============================================================

def build_year(year: int) -> int:
    return build_benchmarks(
        date(year, 1, 1),
        date(year + 1, 1, 1),
        "annual"
    )