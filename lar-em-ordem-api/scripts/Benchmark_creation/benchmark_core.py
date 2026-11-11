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
    "monthly": float(os.getenv("BENCHMARK_MIN_COVERAGE_MONTHLY", "0.90")),
    "annual": float(os.getenv("BENCHMARK_MIN_COVERAGE_ANNUAL", "0.275")),
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
        c.cost,
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
      AND EXISTS (
          SELECT 1
          FROM property_contracts pc
          INNER JOIN residents r ON r.id = pc.resident_id
          WHERE pc.property_id = p.id
            AND pc.is_active = 1
            AND pc.deleted_at IS NULL
            AND (
                SELECT uc.granted
                FROM user_consents uc
                WHERE uc.user_id = r.user_id
                  AND uc.type = 'data_sharing'
                ORDER BY uc.created_at DESC, uc.id DESC
                LIMIT 1
            ) = 1
      )
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
        reference_period, period_start, average_value, average_cost, sample_size
    )
    VALUES (
        :consumption_type_id, :property_type_id, :typology_id, :region,
        :reference_period, :period_start, :average_value, :average_cost, :sample_size
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

    # (property_id, consumption_type_id) -> [valor, custo, dias_consumo, dias_custo]
    property_consumption = defaultdict(lambda: [0.0, 0.0, 0, 0])
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

        property_consumption[key][2] = min(
            property_consumption[key][2] + overlap_days, expected_days
        )

        if row["cost"] is not None:
            property_consumption[key][1] += float(row["cost"]) * overlap_days / total_days
            property_consumption[key][3] = min(
                property_consumption[key][3] + overlap_days, expected_days
            )

        property_data[row["property_id"]] = {
            "property_type_id": row["property_type_id"],
            "typology_id": row["typology_id"],
            "region": row["region"],
        }

    groups = defaultdict(lambda: {"values": [], "costs": []})

    for (property_id, consumption_type_id), (
        total_value,
        total_cost,
        covered_days,
        cost_covered_days,
    ) in property_consumption.items():

        if covered_days < expected_days * min_coverage:
            continue

        value = total_value * expected_days / covered_days
        info = property_data[property_id]

        group_key = (
            consumption_type_id,
            info["property_type_id"],
            info["typology_id"],
            info["region"],
        )

        groups[group_key]["values"].append(value)

        if cost_covered_days >= expected_days * min_coverage:
            cost = total_cost * expected_days / cost_covered_days
            groups[group_key]["costs"].append(cost)

    saved = 0

    with engine.begin() as conn:
        conn.execute(
            DELETE,
            {"reference_period": reference_period, "period_start": window_start},
        )

        for (ctype, ptype, typology, region), group_data in groups.items():
            values = group_data["values"]
            costs = group_data["costs"]

            if len(values) < MIN_SAMPLE:
                continue

            average_cost = (
                round(sum(costs) / len(costs), 2)
                if len(costs) >= MIN_SAMPLE
                else None
            )

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
                    "average_cost": average_cost,
                    "sample_size": len(values),
                },
            )
            saved += 1

    return saved


def build_month(first_day: date) -> int:
    return build_benchmarks(first_day, next_month(first_day), "monthly")


def build_year(year: int) -> int:
    return build_benchmarks(date(year, 1, 1), date(year + 1, 1, 1), "annual")
