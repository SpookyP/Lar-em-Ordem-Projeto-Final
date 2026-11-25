"""
Extracts data from Portuguese Endesa and EPAL invoices and prints it as JSON.

Usage:
    python InvoiceExtract.py Fatura_Endesa.pdf
    python InvoiceExtract.py Fatura_Epal.pdf
    python InvoiceExtract.py fatura.pdf --types-map '{"eletricidade": 1, "agua": 2, "gas": 3}'

Dependency: pip install pdfplumber
"""

import argparse
import json
import re
import sys
import unicodedata
from datetime import datetime

import pdfplumber


# Abbreviations of the Portuguese months.
MONTHS = {
    "jan": "01", "fev": "02", "mar": "03", "abr": "04",
    "mai": "05", "jun": "06", "jul": "07", "ago": "08",
    "set": "09", "out": "10", "nov": "11", "dez": "12",
}


# Patterns that recognise numbers, amounts and dates.
NUMBER_RE = r"[-+]?\d+(?:[.,]\d+)?"
CURRENCY_RE = re.compile(rf"({NUMBER_RE})\s*€", re.IGNORECASE)
DATE_RE = r"\d{1,2}[-/.]\d{1,2}[-/.]\d{4}"
TEXT_DATE_RE = r"\d{1,2}\s+[a-zç]{3}\s+\d{4}"
PERIOD_RE = re.compile(
    rf"({DATE_RE}|{TEXT_DATE_RE})\s+a\s+({DATE_RE}|{TEXT_DATE_RE})",
    re.IGNORECASE,
)


# Strips accents and lowercases, so matching ignores them.
def norm(text):
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text)
    return "".join(ch for ch in text if not unicodedata.combining(ch)).lower()


# Converts Portuguese-formatted numbers (e.g. "1.234,56") to floats.
def parse_number(value):
    if value is None:
        return 0.0

    text = str(value).strip().replace("€", "").replace("EUR", "")
    text = text.replace(" ", "").replace("|", "")
    negative = text.startswith("-") or (text.startswith("(") and text.endswith(")"))
    text = text.replace("(", "").replace(")", "").replace("-", "").replace("+", "")

    if "," in text:
        text = text.replace(".", "").replace(",", ".")

    try:
        number = float(text)
        return -number if negative else number
    except (ValueError, TypeError):
        return 0.0


# Converts numeric ("31/12/2025") and textual ("31 dez 2025") dates to YYYY-MM-DD.
def format_date(value):
    if not value:
        return None

    text = re.sub(r"\s+", " ", value.strip().lower())

    for fmt in ("%d-%m-%Y", "%d/%m/%Y", "%d.%m.%Y"):
        try:
            return datetime.strptime(text, fmt).strftime("%Y-%m-%d")
        except ValueError:
            pass

    match = re.fullmatch(r"(\d{1,2})\s+([a-zç]{3})\s+(\d{4})", text)
    if match:
        day, month_text, year = match.groups()
        month = MONTHS.get(norm(month_text))
        if month:
            return f"{year}-{month}-{day.zfill(2)}"

    return None


# Classifies an item as a tax, a fixed fee or consumption, from its name.
def classify_category(name):
    text = norm(name)

    if any(word in text for word in (
        "iva", "imposto", "taxa", "contribuicao", "mecanismo",
        "dgeg", "audiovisual", "subsolo", "adicional",
    )):
        return "tax"

    if any(word in text for word in (
        "potencia", "termo fixo", "quota servico", "saneamento",
        "residuos urbanos", "disponibilidade", "aluguer",
        "acesso as redes", "abastecimento de agua",
    )):
        return "fixed_fee"

    return "consumption"


# Returns the text of each page and all of it joined; layout mode keeps table rows on one line.
def get_all_text(pdf_path):
    with pdfplumber.open(pdf_path) as pdf:
        pages = [
            page.extract_text(layout=True) or page.extract_text() or ""
            for page in pdf.pages
        ]
    return pages, "\n".join(pages)


# Each supplier prints the invoice number differently.
def extract_invoice_number(text, supplier):
    if supplier == "Endesa":
        patterns = [
            r"N[ºo°]\s*Documento\s*:\s*(\d+)",
            r"N[ºo°]\s*Documento\s+(\d+)",
        ]
    else:
        patterns = [
            r"FATURA\s*n[ºo°]?\s*FT\s*([\d]+/[\d]+)",
            r"FATURA\s*n[ºo°]?\s*FT\s*([\d./]+)",
        ]

    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            return match.group(1).strip()

    return None


def extract_issue_date(text):
    patterns = [
        rf"Data\s+de\s+Emiss[aã]o\s*:\s*({TEXT_DATE_RE}|{DATE_RE})",
        rf"FATURA\s*n[ºo°]?\s*FT\s*[\d/]+\s*,?\s*emitida\s+em\s+({DATE_RE}|{TEXT_DATE_RE})",
    ]

    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            result = format_date(match.group(1))
            if result:
                return result

    return None


# Extracts the start and end dates of the billing period.
def extract_period(text):
    match = re.search(
        r"Per[ií]odo de Fatura[cç][aã]o(?:\s*\([^)]*\))?\s*:\s*"
        rf"({DATE_RE}|{TEXT_DATE_RE})\s+a\s+({DATE_RE}|{TEXT_DATE_RE})",
        text,
        re.IGNORECASE,
    )

    if match:
        return format_date(match.group(1)), format_date(match.group(2))

    return None, None


# Parses the first group of the pattern as an amount; None if it doesn't match.
def extract_currency_value(pattern, text):
    match = re.search(pattern, text, re.IGNORECASE | re.DOTALL)
    return parse_number(match.group(1)) if match else None


# Reads the table rows of an Endesa page: quantity and unit (kWh, days, months, m³)
# and the last € amount on the line.
def parse_endesa_rows(page_text):
    rows = []

    for line in page_text.splitlines():
        unit_match = re.search(
            r"(?P<qty>\d+(?:[.,]\d+)?)\s*(?P<unit>kWh|dias|meses|m[³3])\b",
            line,
            re.IGNORECASE,
        )

        if not unit_match:
            continue

        after_unit = line[unit_match.end():]
        currency_values = CURRENCY_RE.findall(after_unit)

        if not currency_values:
            continue

        qty = parse_number(unit_match.group("qty"))
        unit_raw = unit_match.group("unit").lower()
        unit = {
            "kwh": "kWh",
            "dias": "dias",
            "meses": "meses",
            "m3": "m³",
            "m³": "m³",
        }.get(unit_raw, unit_raw)

        rows.append({
            "amount": qty,
            "unit": unit,
            "cost": parse_number(currency_values[-1]),
        })

    return rows


# Names and categories of the electricity items, in the order the rows appear on the Endesa page.
ELECTRICITY_DEFINITIONS = [
    ("Termo de Energia (Real)", "consumption"),
    ("Termo de Energia (Real)", "consumption"),
    ("Termo de Potência (6.90 kVA)", "fixed_fee"),
    ("Termo Fixo Acesso às Redes", "fixed_fee"),
    ("Mecanismo DL 33/2022", "tax"),
    ("Contribuição Audiovisual", "tax"),
    ("Taxa Exploração DGEG (DL-4/93)", "tax"),
    ("Imposto Especial Consumo (Real)", "tax"),
]


# Names and categories of the gas items, in the order the rows appear on the Endesa page.
GAS_DEFINITIONS = [
    ("Termo de Energia (Real)", "consumption"),
    ("Termo Fixo (Escalão 2)", "fixed_fee"),
    ("Termo Fixo Acesso às Redes", "fixed_fee"),
    ("Imposto Especial Consumo GN (Real)", "tax"),
    ("Taxa Ocupação Subsolo Variável (Real)", "tax"),
    ("Taxa Ocupação Subsolo Fixa (Real)", "tax"),
]


# Pairs each row with the definition in the same position on Endesa; rows beyond the list are ignored.
def build_endesa_items(rows, definitions):
    items = []

    for index, row in enumerate(rows):
        if index >= len(definitions):
            continue

        name, category = definitions[index]
        amount, unit = row["amount"], row["unit"]

        items.append({
            "name": name,
            "category": category,
            "amount": amount,
            "unit": unit,
            "cost": row["cost"],
        })

    return items


# VAT lines of an Endesa page, without repeated ones.
def parse_endesa_vat(page_text):
    items = []
    seen = set()

    for line in page_text.splitlines():
        match = re.search(
            rf"\([a-z]\)\s*IVA\s*(\d+)\s*%\s*({NUMBER_RE})\s*€\s*({NUMBER_RE})\s*€",
            line,
            re.IGNORECASE,
        )

        if not match:
            continue

        vat_rate = int(match.group(1))
        base_amount = parse_number(match.group(2))
        vat_cost = parse_number(match.group(3))
        key = (vat_rate, base_amount, vat_cost)

        if key in seen:
            continue

        seen.add(key)

        items.append({
            "name": f"IVA {vat_rate}%",
            "category": "tax",
            "amount": base_amount,
            "unit": "EUR de base",
            "cost": vat_cost,
        })

    return items


# Electricity and gas consumptions of an Endesa invoice, and the invoice total.
def parse_endesa(pages, text, types_map):
    electricity_page = next((
        p for p in pages
        if re.search(r"\bLUZ\b", p, re.IGNORECASE)
        and re.search(r"Per[ií]odo de Fatura[cç][aã]o", p, re.IGNORECASE)
    ), "")

    gas_page = next((
        p for p in pages
        if re.search(r"\bGÁS\b|\bGAS\b", p, re.IGNORECASE)
        and re.search(r"Per[ií]odo de Fatura[cç][aã]o", p, re.IGNORECASE)
    ), "")

    consumptions = []
    invoice_total = 0.0

    # Electricity.
    if electricity_page:
        rows = parse_endesa_rows(electricity_page)
        luz_items = build_endesa_items(rows, ELECTRICITY_DEFINITIONS)
        luz_items.extend(parse_endesa_vat(electricity_page))

        luz_cost = extract_currency_value(
            r"TOTAL DA FATURA DE LUZ\s*([\d.,]+)", text
        )

        if luz_cost is None or luz_cost <= 0:
            luz_cost = extract_currency_value(
                r"Smqu\s*-\s*Processado[^\n]*?([\d.,]+)\s*€",
                electricity_page,
            ) or 0.0

        luz_qty = sum(
            i["amount"] for i in luz_items
            if i["category"] == "consumption" and i["unit"] == "kWh"
        )

        if luz_items:
            consumptions.append({
                "consumption_type_id": types_map.get("eletricidade", 1),
                "amount": luz_qty or None,
                "cost": luz_cost,
                "consumption_items": luz_items,
            })

        invoice_total += luz_cost

    # Gas.
    if gas_page:
        rows = parse_endesa_rows(gas_page)
        gas_items = build_endesa_items(rows, GAS_DEFINITIONS)
        gas_items.extend(parse_endesa_vat(gas_page))

        gas_cost = extract_currency_value(
            r"TOTAL DA FATURA DE GÁS\s*([\d.,]+)", text
        )

        if gas_cost is None or gas_cost <= 0:
            gas_cost = extract_currency_value(
                r"MXKI\s*-\s*Processado[^\n]*?([\d.,]+)\s*€",
                gas_page,
            ) or 0.0

        gas_qty = sum(
            i["amount"] for i in gas_items
            if i["category"] == "consumption"
            and i["unit"] in ("kWh", "m³")
        )

        if gas_items:
            consumptions.append({
                "consumption_type_id": types_map.get("gas", 3),
                "amount": gas_qty or None,
                "cost": gas_cost,
                "consumption_items": gas_items,
            })

        invoice_total += gas_cost

    # If the invoice shows an overall total, it replaces the sum of the services.
    top_total = extract_currency_value(
        r"TOTAL A DEBITAR\s*([\d.,]+)\s*€", text
    )

    if top_total is None:
        top_total = extract_currency_value(
            r"SALDO ATUAL\s*([\d.,]+)\s*€", text
        )

    if top_total is not None and top_total > 0:
        invoice_total = top_total

    return consumptions, invoice_total


# Service lines and VAT of an EPAL invoice.
def parse_epal_detail_items(text):
    lines = text.splitlines()

    heading_patterns = [
        (
            re.compile(r"EPAL\s*-\s*Abastecimento de [ÁA]gua", re.IGNORECASE),
            "EPAL - Abastecimento de Água (Quota Serviço)",
        ),
        (
            re.compile(r"CMLisboa\s*-\s*Saneamento", re.IGNORECASE),
            "CMLisboa - Saneamento (Tarifa de Disponibilidade)",
        ),
        (
            re.compile(r"CMLisboa\s*-\s*Res[ií]duos Urbanos", re.IGNORECASE),
            "CMLisboa - Resíduos Urbanos (Tarifa de Disponibilidade)",
        ),
    ]

    items = []
    seen = set()

    # Service lines.

    for idx, line in enumerate(lines):
        selected = None

        for pattern, item_name in heading_patterns:
            if pattern.search(line):
                selected = item_name
                break

        if not selected or selected in seen:
            continue

        row_data = None

        for candidate in lines[idx + 1:idx + 8]:
            date_match = PERIOD_RE.search(candidate)
            if not date_match:
                continue

            remainder = candidate[date_match.end():]
            values = re.findall(NUMBER_RE, remainder)

            # Table columns: quantity | unit price | billed amount | amount due | VAT rate.
            # The cost is the second-to-last value; the last one is the VAT rate.
            if len(values) >= 3:
                quantity = parse_number(values[0])
                cost = parse_number(values[-2])
                row_data = (quantity, cost)
                break

        if row_data is None:
            continue

        quantity, cost = row_data

        items.append({
            "name": selected,
            "category": "fixed_fee",
            "amount": quantity,
            "unit": "dias",
            "cost": cost,
        })

        seen.add(selected)

    # VAT.

    normalized_text = re.sub(r"\s+", " ", text)

    vat_pattern = re.compile(
        rf"IVA\s*(\d+)\s*%\s*"
        rf"\(\s*({NUMBER_RE})\s*"
        rf"x\s*\d+\s*%\s*\)\s*"
        rf"({NUMBER_RE})",
        re.IGNORECASE
    )

    vat_seen = set()

    for match in vat_pattern.finditer(normalized_text):
        vat_rate = int(match.group(1))
        base_amount = parse_number(match.group(2))
        vat_cost = parse_number(match.group(3))
        key = (vat_rate, base_amount, vat_cost)

        if key in vat_seen:
            continue

        vat_seen.add(key)

        items.append({
            "name": f"IVA {vat_rate}%",
            "category": "tax",
            "amount": base_amount,
            "unit": "EUR de base",
            "cost": vat_cost,
        })

    return items


# Water consumption and total of an EPAL invoice.
def parse_epal(pages, text, types_map):
    items = parse_epal_detail_items(text)
    total = extract_currency_value(r"Valor a pagar\s*([\d.,]+)\s*€?", text)

    if total is None:
        total = extract_currency_value(
            r"PAGAMENTO DO TOTAL DA CONTA CLIENTE.*?Montante\.?\s*([\d.,]+)\s*€",
            text,
        )

    if total is None:
        total = 0.0


    usage_match = re.search(
        rf"({NUMBER_RE})\s*litros",
        text,
        re.IGNORECASE,
    )
    # EPAL prints litres; the database and the benchmarks use m³.
    water_amount = (
        round(parse_number(usage_match.group(1)) / 1000, 3)
        if usage_match
        else None
    )

    consumptions = []

    if items:
        consumptions.append({
            "consumption_type_id": types_map.get(
                "agua",
                types_map.get("água", 2),
            ),
            "amount": water_amount,
            "unit": "m³",
            "cost": total,
            "consumption_items": items,
        })

    return consumptions, total


# Detects the supplier and collects the invoice's main data.
def extract(pdf_path, types_map=None):
    types_map = types_map or {}
    pages, text = get_all_text(pdf_path)
    text_norm = norm(text)

    # The supplier comes from the document text, not from the file name.
    if "endesa" in text_norm:
        supplier = "Endesa"
    elif (
        "epal" in text_norm
        or "empresa portuguesa das aguas livres" in text_norm
    ):
        supplier = "EPAL"
    else:
        supplier = "Outro"

    invoice_number = extract_invoice_number(text, supplier)
    issue_date = extract_issue_date(text)
    period_start, period_end = extract_period(text)

    # Each known supplier has its own extractor; for any other only the total is read.
    if supplier == "Endesa":
        consumptions, total_amount = parse_endesa(pages, text, types_map)
    elif supplier == "EPAL":
        consumptions, total_amount = parse_epal(pages, text, types_map)
    else:
        consumptions = []
        total_amount = extract_currency_value(
            r"Valor a pagar\s*([\d.,]+)\s*€?", text
        ) or 0.0

    return {
        "supplier": supplier,
        "invoice_number": invoice_number,
        "issue_date": issue_date,
        "period_start": period_start,
        "period_end": period_end,
        "total_amount": total_amount,
        "consumptions": consumptions,
    }


# Prints the result as JSON; on error it writes to stderr and exits with 1
# (InvoiceExtractionService logs it).
def main():
    parser = argparse.ArgumentParser(
        description="Extrai dados de faturas Endesa e EPAL para JSON."
    )
    parser.add_argument("pdf_path", help="Caminho para o PDF da fatura")
    parser.add_argument(
        "--types-map",
        default="{}",
        help='JSON opcional com os IDs, p.ex. \'{"eletricidade":1,"gas":2,"agua":3}\'',
    )

    args = parser.parse_args()

    try:
        types_map = json.loads(args.types_map)

        # Laravel sends the map through stdin (avoids quoting problems on Windows).
        if not types_map and not sys.stdin.isatty():
            raw = sys.stdin.read().strip()

            if raw:
                types_map = json.loads(raw)

        if not isinstance(types_map, dict):
            raise ValueError("O mapa de tipos deve ser um objeto JSON")

        # Normalises the keys (no accents, lowercase): "Água" -> "agua".
        types_map = {norm(key): value for key, value in types_map.items()}

        result = extract(args.pdf_path, types_map)
        print(json.dumps(result, ensure_ascii=False, indent=2))

    except (OSError, ValueError, json.JSONDecodeError) as exc:
        print(f"Erro: {exc}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()