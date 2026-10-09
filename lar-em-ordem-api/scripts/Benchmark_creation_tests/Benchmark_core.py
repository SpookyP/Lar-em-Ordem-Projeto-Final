"""
Núcleo do cálculo de benchmarks de consumo.
 
Ideia geral:
  1. Vai buscar à BD os consumos que tocam o período (mês ou ano) a processar.
  2. Para cada propriedade e tipo de consumo, calcula quanto consumiu nesse período
     (repartindo faturas que cruzam meses proporcionalmente aos dias).
  3. Agrupa propriedades parecidas (mesmo tipo de consumo, tipo de propriedade,
     tipologia e região) e calcula a média de cada grupo.
  4. Grava a média em consumption_benchmarks (só se o grupo tiver propriedades suficientes).
"""

import os
from collections import defaultdict
from datetime import date, timedelta
from pathlib import Path

from dotenv import load_dotenv
from sqlalchemy import create_engine, text
from sqlalchemy.engine import URL

# ============================================================
# CONFIGURAÇÃO
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[2]
load_dotenv(BASE_DIR / ".env")

# Defaults baixos para testes:
#   BENCHMARK_MIN_SAMPLE=5
#   BENCHMARK_MIN_COVERAGE_MONTHLY=0.9
#   BENCHMARK_MIN_COVERAGE_ANNUAL=0.75

# Mínimo de propriedades por grupo para o benchmark ser gravado.
MIN_SAMPLE = int(os.getenv("BENCHMARK_MIN_SAMPLE", "3"))

# Percentagem mínima do período que tem de estar coberta por faturas.
MIN_COVERAGE = {
    "monthly": float(os.getenv("BENCHMARK_MIN_COVERAGE_MONTHLY", "0.25")),
    "annual": float(os.getenv("BENCHMARK_MIN_COVERAGE_ANNUAL", "0.25")),
}

REGION_COLUMN = os.getenv("BENCHMARK_REGION_COLUMN", "district")

if not REGION_COLUMN.replace("_", "").isalnum():
    raise RuntimeError("BENCHMARK_REGION_COLUMN inválido")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_DATABASE = os.getenv("DB_DATABASE")
DB_USERNAME = os.getenv("DB_USERNAME")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

if not DB_DATABASE:
    raise RuntimeError("DB_DATABASE não encontrado no ficheiro .env")

if not DB_USERNAME:
    raise RuntimeError("DB_USERNAME não encontrado no ficheiro .env")

engine = create_engine(
    URL.create(
        "mysql+pymysql",
        username=DB_USERNAME,
        password=DB_PASSWORD,
        host=DB_HOST,
        port=DB_PORT,
        database=DB_DATABASE,
    )
)

# ============================================================
# QUERIES
# ============================================================


# Consumos que se sobrepõem à janela [window_start, window_end),
# já com as características da propriedade (tipo, tipologia, região).
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

# Apaga o benchmark anterior do mesmo período (é isto que permite reprocessar sem duplicar)
DELETE = text("""
    DELETE FROM consumption_benchmarks
    WHERE reference_period = :reference_period
      AND period_start = :period_start
""")

INSERT = text("""
    INSERT INTO consumption_benchmarks (
        consumption_type_id, property_type_id, typology_id, region,
        reference_period, period_start, average_value, sample_size
    )
    VALUES (
        :consumption_type_id, :property_type_id, :typology_id, :region,
        :reference_period, :period_start, :average_value, :sample_size
    )
""")


# ============================================================
# DATAS
# ============================================================

def month_start(d: date) -> date:
    return d.replace(day=1)


def next_month(d: date) -> date:
    return (d.replace(day=28) + timedelta(days=4)).replace(day=1)


def _to_date(value):
    return value.date() if hasattr(value, "date") else value


# ============================================================
# CÁLCULO DOS BENCHMARKS
# ============================================================

def build_benchmarks(window_start: date, window_end: date, reference_period: str) -> int:
    """Calcula e grava os benchmarks do intervalo [window_start, window_end).
    Devolve o número de grupos gravados."""

    expected_days = (window_end - window_start).days
    min_coverage = MIN_COVERAGE[reference_period]

    # (property_id, consumption_type_id) -> [valor, dias_cobertos]
    property_consumption = defaultdict(lambda: [0.0, 0])
    property_data = {}

    with engine.connect() as conn:
        rows = conn.execute(
            FETCH, {"window_start": window_start, "window_end": window_end}
        ).mappings().all()

    for row in rows:
        start = _to_date(row["period_start"])
        end = _to_date(row["period_end"])

        if end < start:
            continue

        total_days = (end - start).days + 1
        overlap_start = max(start, window_start)
        overlap_end = min(end, window_end - timedelta(days=1))

        if overlap_end < overlap_start:
            continue

        overlap_days = (overlap_end - overlap_start).days + 1
        key = (row["property_id"], row["consumption_type_id"])

        property_consumption[key][0] += float(row["amount"]) * overlap_days / total_days
        # nunca conta mais dias do que o período tem (faturas sobrepostas)
        property_consumption[key][1] = min(
            property_consumption[key][1] + overlap_days, expected_days
        )

        property_data[row["property_id"]] = {
            "property_type_id": row["property_type_id"],
            "typology_id": row["typology_id"],
            "region": row["region"],
        }

    groups = defaultdict(list)

    for (property_id, consumption_type_id), (total_value, covered_days) in property_consumption.items():
        if covered_days < expected_days * min_coverage:
            continue

        value = total_value * expected_days / covered_days
        info = property_data[property_id]

        groups[(
            consumption_type_id,
            info["property_type_id"],
            info["typology_id"],
            info["region"],
        )].append(value)

    saved = 0

    with engine.begin() as conn:
        conn.execute(
            DELETE,
            {"reference_period": reference_period, "period_start": window_start},
        )

        for (ctype, ptype, typology, region), values in groups.items():
            if len(values) < MIN_SAMPLE:
                continue

            conn.execute(
                INSERT,
                {
                    "consumption_type_id": ctype,
                    "property_type_id": ptype,
                    "typology_id": typology,
                    "region": region,
                    "reference_period": reference_period,
                    "period_start": window_start,
                    "average_value": round(sum(values) / len(values), 3),
                    "sample_size": len(values),
                },
            )
            saved += 1

    return saved


def build_month(first_day: date) -> int:
    return build_benchmarks(first_day, next_month(first_day), "monthly")


def build_year(year: int) -> int:
    return build_benchmarks(date(year, 1, 1), date(year + 1, 1, 1), "annual")